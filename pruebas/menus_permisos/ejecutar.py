#!/usr/bin/env python3
"""Ejecuta equivalencia y revocación de menús del paquete instalado, sin BD/red."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys

raiz = Path(__file__).resolve().parents[2]
vendor = Path(os.environ.get('QA_VENDOR', raiz / 'vendor')).resolve()
try:
    paquete = vendor / 'gamboa.martin/administrador'
    script = paquete / 'pruebas/rendimiento_menu/probar.php'
    registro = json.loads(Path(__file__).with_name('registro.json').read_text())
    proceso = subprocess.run(['php', '-d', 'display_errors=stderr', '-d', 'log_errors=0', str(script)],
                             capture_output=True, text=True, timeout=45)
    if proceso.returncode or proceso.stderr:
        raise RuntimeError('La equivalencia del paquete falló: ' + proceso.stderr[-1000:])
    resultado = json.loads(proceso.stdout)
    if (resultado.get('estado') != 'PASS_MENU_EQUIVALENCIA'
            or resultado.get('casos') != registro['casos_previstos']
            or resultado.get('aserciones') != registro['aserciones_previstas']
            or resultado.get('sin_bd_red') is not True
            or resultado.get('consultas_fixture_antes') != registro['consultas_fixture_antes']
            or resultado.get('consultas_fixture_despues') != registro['consultas_fixture_despues']):
        raise RuntimeError('El resultado no coincide con el contrato registrado.')
    instalados = json.loads((vendor / 'composer/installed.json').read_text())
    identidad = next(p for p in instalados['packages'] if p['name'] == 'gamboa.martin/administrador')
    archivos = ['orm/adm_menu.php', 'orm/_base_accion.php', 'pruebas/rendimiento_menu/probar.php',
                'pruebas/rendimiento_menu/adm_menu_legacy.php.txt',
                'pruebas/rendimiento_menu/base_accion_legacy.php.txt']
    resultado['paquete'] = {'version': identidad['version'],
                           'reference': identidad.get('dist', {}).get('reference'),
                           'fuentes_sha256': {n: hashlib.sha256((paquete / n).read_bytes()).hexdigest() for n in archivos}}
    print(json.dumps(resultado, ensure_ascii=False, indent=2))
    print('PASS_MENUS_PERMISOS')
except (OSError, ValueError, RuntimeError, StopIteration, subprocess.TimeoutExpired) as error:
    print(f'Contrato de menús bloqueado: {error}', file=sys.stderr)
    sys.exit(1)
