#!/usr/bin/env bash
set -euo pipefail
umask 077
qa=$(mktemp -d /tmp/qa-baja-empresa-XXXXXXXX)
base="${QA_CSD_ENGINE_ROOT:?Indique la ruta del motor de pruebas verificado}/usr"
app="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd -P)"
pid=''
limpiar() {
    if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then kill -TERM "$pid"; wait "$pid" || true; fi
    [[ "$qa" == /tmp/qa-baja-empresa-* ]] && rm -rf -- "$qa"
}
trap limpiar EXIT
trap 'exit 143' TERM
trap 'exit 130' INT
mkdir "$qa/data" "$qa/tmp" "$qa/disabled"
test "$(sha256sum "$base/sbin/mariadbd" | cut -d' ' -f1)" = "$(python3 -c 'import sys,json; print(json.load(open(sys.argv[1]))["usr/sbin/mariadbd"])' "$app/pruebas/csd_integral/infra/engine-pins.json")"
common=(--no-defaults --datadir="$qa/data" --socket="$qa/mysql.sock" --pid-file="$qa/mysql.pid" --tmpdir="$qa/tmp" --skip-networking --skip-log-bin --event-scheduler=OFF --performance-schema=OFF --local-infile=OFF --secure-file-priv="$qa/disabled" --innodb-use-native-aio=OFF --innodb-buffer-pool-size=64M --innodb-log-file-size=64M --basedir="$base" --lc-messages-dir="$base/share/mysql" --plugin-dir="$base/lib/mysql/plugin")
{ printf 'CREATE DATABASE mysql;\nUSE mysql;\nSET @auth_root_socket=NULL;\n'; for f in mysql_system_tables.sql mysql_performance_tables.sql mysql_system_tables_data.sql fill_help_tables.sql maria_add_gis_sp_bootstrap.sql mysql_sys_schema.sql; do cat "$base/share/mysql/$f"; done; } | "$base/sbin/mariadbd" "${common[@]}" --bootstrap >"$qa/bootstrap.log" 2>&1 || { tail -15 "$qa/bootstrap.log"; exit 1; }
"$base/sbin/mariadbd" "${common[@]}" >"$qa/motor.log" 2>&1 &
pid=$!
client=(mysql --no-defaults --protocol=SOCKET --socket="$qa/mysql.sock" --user=root --batch --skip-column-names)
for ((n=0;n<40;n++)); do if "${client[@]}" -e 'SELECT 1' >/dev/null 2>&1; then break; fi; sleep 0.2; done
"${client[@]}" -e 'CREATE DATABASE qa_baja_empresa;'
printf 'empresa_baja\n' > "$qa/mysql.sock.fixture"
php "$app/pruebas/empresa_baja/probar_alcance.php" "$qa/mysql.sock"
kill -TERM "$pid"
wait "$pid"
pid=''
test ! -e "$qa/mysql.sock"
printf 'Motor efímero detenido; sin cambios en bases activas.\n'
