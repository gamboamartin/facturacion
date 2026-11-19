#!/usr/bin/env python3
"""Agrega batería PHPUnit vigente, suites complementarias y frontend sin sustituirlas."""
import json
from pathlib import Path
import subprocess
import sys

raiz = Path(__file__).resolve().parent.parent
registro = json.loads((raiz / 'pruebas/regresion/registro.json').read_text())
pasos = [['python3', str(raiz / 'pruebas/regresion/ejecutar_phpunit.py')]]
for suite in registro.get('suites_complementarias', []):
    pasos.append(['python3', str(raiz / suite['ejecutor'])])
for archivo in sorted((raiz / 'pruebas/frontend').glob('*/probar_*.py')):
    pasos.append(['python3', str(archivo)])
informe = {'estado': 'BLOQUEADO', 'pasos': []}
try:
    for comando in pasos:
        # El gate integral necesita margen para cerrar su namespace tras timeout propio.
        limite = 300 if Path(comando[1]).relative_to(raiz).as_posix() in {'pruebas/csd_integral/ejecutar.py', 'pruebas/csd_integral/ejecutar_fpm.py'} else 240
        p = subprocess.run(comando, cwd=raiz, capture_output=True, text=True, timeout=limite)
        informe['pasos'].append({'ejecutor': str(Path(comando[1]).relative_to(raiz)),
                                 'codigo': p.returncode, 'salida': p.stdout, 'errores': p.stderr})
        if p.returncode:
            raise RuntimeError('Batería incompleta: detener publicación y discutir resultado')
    informe['estado'] = 'PASS_BATERIAS_REGISTRADAS'
except Exception as error:
    informe['error'] = str(error)
(raiz / 'pruebas/resultados/completa.json').write_text(json.dumps(informe, ensure_ascii=False, indent=2) + '\n')
print(informe['estado'])
sys.exit(0 if informe['estado'] == 'PASS_BATERIAS_REGISTRADAS' else 1)
