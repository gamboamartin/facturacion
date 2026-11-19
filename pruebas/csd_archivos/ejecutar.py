#!/usr/bin/env python3
"""Registro ejecutable focal CSD. Fuentes externas explícitas, sin DB ni red."""
import json
import os
from pathlib import Path
import subprocess
import sys

base = Path(__file__).resolve().parent
raiz = base.parent.parent
registro = json.loads((base / 'registro.json').read_text())
informe = {'estado': 'BLOQUEADO', 'suites': [], 'limites': registro['limites']}
requeridas = ['FUENTE_DOCUMENTO', 'FUENTE_DOCUMENTO_ANTIGUO', 'AUTOLOAD_PRUEBAS',
              'FUENTE_PLUGIN_FILES', 'FUENTE_ERRORES', 'FUENTE_SSL']
bloqueadas = ('shell_exec,exec,system,passthru,proc_open,popen,pcntl_exec,fsockopen,'
              'pfsockopen,stream_socket_client,curl_exec,curl_multi_exec,socket_create,socket_connect')
try:
    if any(not Path(os.environ.get(nombre, '/no-existe')).is_file() for nombre in requeridas):
        raise RuntimeError('Faltan fuentes explícitas para las pruebas')
    entorno = dict(os.environ, FUENTE_CONTROLADOR_CSD=str(raiz / 'controllers/controlador_fc_csd.php'))
    for suite in registro['suites']:
        total = 0
        for escenario in suite.get('escenarios', [None]):
            comando = ['php', '-n', '-d', 'extension=pdo', '-d', 'allow_url_fopen=0',
                       '-d', 'allow_url_include=0', '-d', 'disable_functions=' + bloqueadas,
                       str(base / suite['archivo'])]
            if escenario:
                comando.append(escenario)
            p = subprocess.run(comando, cwd=raiz, env=entorno, capture_output=True, text=True, timeout=40)
            salida = p.stdout.splitlines()
            if p.returncode or p.stderr:
                raise RuntimeError('Fallo o warning en ' + suite['archivo'])
            if escenario:
                dato = json.loads(p.stdout)
                if dato['estado'] != 'PASS' or not dato['limpieza']:
                    raise RuntimeError('Escenario no aprobado: ' + escenario)
                total += 1
            else:
                total += sum(linea.startswith('PASS ') for linea in salida)
                if suite['archivo'] not in ['dependencia_antigua.php', 'plugins_antiguo.php'] and 'LIMPIEZA_VERIFICADA' not in salida:
                    raise RuntimeError('Limpieza no acreditada')
        if total != suite['casos']:
            raise RuntimeError('Cantidad distinta del registro: ' + suite['archivo'])
        informe['suites'].append({'archivo': suite['archivo'], 'casos': total, 'estado': 'PASS'})
    informe['estado'] = 'PASS_FOCAL'
except Exception as error:
    informe['error'] = str(error)
resultado = raiz / 'pruebas/resultados/csd_archivos.json'
resultado.parent.mkdir(exist_ok=True)
resultado.write_text(json.dumps(informe, ensure_ascii=False, indent=2) + '\n')
print(json.dumps(informe, ensure_ascii=False))
sys.exit(0 if informe['estado'] == 'PASS_FOCAL' else 1)
