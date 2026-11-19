#!/usr/bin/env python3
"""Gate obligatorio FPM local: un alta exitosa sintética, sin equivalencia con UI."""
from __future__ import annotations

import datetime
import hashlib
import json
import os
from pathlib import Path
import signal
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
PARAMETROS = {
    '--engine-root': 'QA_CSD_ENGINE_ROOT',
    '--deps': 'QA_CSD_DEPS',
    '--documento': 'QA_CSD_DOCUMENTO',
    '--plugins': 'QA_CSD_PLUGINS',
    '--ddl': 'QA_CSD_DDL',
}


def exigir(condicion: bool, codigo: str) -> None:
    if not condicion:
        raise RuntimeError(codigo)


def uno(registros: list[dict], clave: str) -> dict:
    encontrados = [registro for registro in registros if clave in registro]
    exigir(len(encontrados) == 1, 'evidencia_unica_requerida:' + clave)
    return encontrados[0]


def guardar_json(path: Path, datos: dict) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile('w', dir=path.parent, delete=False, encoding='utf-8') as temporal:
        json.dump(datos, temporal, ensure_ascii=False, indent=2)
        temporal.write('\n')
        nombre = temporal.name
    os.replace(nombre, path)


def comprobar(stdout: str, codigo: int, rutas: dict[str, Path]) -> dict:
    exigir(codigo == 0, 'infra_exit_no_cero')
    registros = [json.loads(linea) for linea in stdout.splitlines() if linea.strip()]
    hashes = uno(registros, 'fuentes_sha256_antes')
    motor = uno(registros, 'alta_controlador')
    namespace = uno(registros, 'namespace_revisado')
    launcher = uno(registros, 'launcher_exit')
    exigir(hashes.get('fuentes_sin_cambios_durante_ejecucion') is True, 'fuentes_modificadas')
    exigir(bool(hashes['fuentes_sha256_antes']), 'sin_hashes')
    for clave in ['aislamiento_verificado', 'fpm_identidad_verificada', 'shutdown_identidad_verificada',
                  'shutdown_enviado', 'motor_detenido', 'fpm_socket_retirado']:
        exigir(motor.get(clave) is True, clave + '_no_acreditado')
    for clave in ['alta_fcgi_exit', 'sonda_exit', 'fpm_exit', 'shutdown_exit', 'motor_exit']:
        exigir(motor.get(clave) == 0, clave + '_fallido')
    exigir(motor.get('fpm_workers_restantes') == [], 'workers_restantes')
    exigir(namespace.get('namespace_revisado') == motor.get('netns') and bool(motor.get('netns')), 'namespace_no_coincide')
    exigir(namespace.get('procesos_namespace_restantes_visibles') == [], 'procesos_namespace_restantes')
    exigir(launcher.get('procesos_grupo_restantes') == [] and launcher.get('launcher_exit') == 0, 'launcher_incompleto')
    exigir(launcher.get('host_sin_bind_escribible') is True, 'host_escribible')
    alta = motor['alta_controlador']
    exigir(alta.get('estado') == 'PASS' and alta.get('sapi') == 'fpm-fcgi', 'alta_no_aprobada')
    for clave in ['uploads_sapi_reales', 'constructor_real_completado', 'retorno_controlador_completado']:
        exigir(alta.get(clave) is True, clave + '_no_acreditado')
    for clave in ['rechazado', 'fatal_presente', 'transaccion_pendiente', 'salida_cruda_descartada']:
        exigir(alta.get(clave) is False, clave + '_inesperado')
    exigir(alta.get('ubicaciones_error_estructuradas') == [], 'errores_estructurados')
    exigir(alta.get('delta_registros') == {
        'fc_csd': 1, 'fc_csd_etapa': 9, 'fc_cer_csd': 1, 'fc_key_csd': 1,
        'fc_cer_pem': 1, 'fc_key_pem': 1, 'doc_documento': 4}, 'delta_registros_incorrecto')
    return {'casos_ejecutados': 1, 'caso': 'alta_exitosa_controlador_fpm_local',
            'alta_controlador': alta, 'aislamiento_verificado': True, 'shutdown_verificado': True,
            'alcance': 'FastCGI privado local; no login, dispatcher, navegador ni FPM de FC3',
            'negativos': 'Acreditados por suites ORM/focales separadas, no por esta petición FPM'}


def main() -> int:
    ahora = datetime.datetime.now(datetime.timezone.utc).isoformat()
    resumen = {'suite': 'csd_fpm_local', 'tipo': 'integracion', 'obligatoria': True, 'estado': 'BLOQUEADO', 'fecha_utc': ahora}
    proceso = None
    evidencia = None
    try:
        rutas = {}
        for argumento, variable in PARAMETROS.items():
            valor = os.environ.get(variable)
            exigir(bool(valor), 'recurso_no_configurado:' + variable)
            rutas[argumento] = Path(valor).resolve(strict=True)
        git = ROOT / '.git'
        exigir(git.is_dir() and not git.is_symlink(), 'directorio_evidencia_privado_no_disponible')
        historico = git / 'qa/csd-integral/ejecuciones'
        historico.mkdir(parents=True, exist_ok=True)
        evidencia = Path(tempfile.mkdtemp(prefix='fpm-', dir=historico))
        comando = [sys.executable, str(ROOT / 'pruebas/csd_integral/fpm/ejecutar.py'), '--alta-controlador']
        for argumento, ruta in rutas.items():
            comando += [argumento, str(ruta)]
        proceso = subprocess.Popen(comando, cwd=ROOT, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, start_new_session=True)
        try:
            stdout, stderr = proceso.communicate(timeout=240)
        except subprocess.TimeoutExpired:
            os.killpg(proceso.pid, signal.SIGTERM)
            try:
                stdout, stderr = proceso.communicate(timeout=10)
            except subprocess.TimeoutExpired:
                os.killpg(proceso.pid, signal.SIGKILL)
                stdout, stderr = proceso.communicate(timeout=5)
            (evidencia / 'stdout.jsonl').write_text(stdout)
            (evidencia / 'stderr.txt').write_text(stderr)
            raise RuntimeError('timeout_integracion_cierre_no_acreditado')
        (evidencia / 'stdout.jsonl').write_text(stdout)
        (evidencia / 'stderr.txt').write_text(stderr)
        exigir(stderr.strip() == '', 'stderr_infra_presente')
        resumen.update(comprobar(stdout, proceso.returncode, rutas))
        resumen['estado'] = 'PASS'
        resumen['evidencia_sha256'] = hashlib.sha256(stdout.encode()).hexdigest()
    except Exception as error:
        resumen['bloqueo'] = str(error) if isinstance(error, RuntimeError) else type(error).__name__
    finally:
        if proceso is not None and proceso.poll() is None:
            os.killpg(proceso.pid, signal.SIGTERM)
            try:
                proceso.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(proceso.pid, signal.SIGKILL)
                proceso.wait(timeout=5)
        if evidencia:
            guardar_json(evidencia / 'resumen.json', resumen)
        guardar_json(ROOT / 'pruebas/resultados/csd_fpm_local.json', resumen)
    print(json.dumps(resumen, ensure_ascii=False))
    return 0 if resumen['estado'] == 'PASS' else 1


if __name__ == '__main__':
    sys.exit(main())
