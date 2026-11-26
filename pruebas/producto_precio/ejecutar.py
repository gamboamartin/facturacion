#!/usr/bin/env python3
"""Contratos de validación de precio del producto, sin configuración privada ni BD."""
import hashlib
import json
from pathlib import Path
import subprocess
import sys

root = Path(__file__).resolve().parents[2]
try:
    registro = json.loads(Path(__file__).with_name('registro.json').read_text())
    fuentes = {n: hashlib.sha256((root / n).read_bytes()).hexdigest() for n in registro['fuentes']}
    proceso = subprocess.run(['php', '-d', 'display_errors=stderr', '-d', 'log_errors=0',
                              str(Path(__file__).with_name('probar.php'))],
                             cwd=root, capture_output=True, text=True, timeout=30)
    if proceso.returncode or proceso.stderr:
        raise RuntimeError('PHP no acreditó la regresión: ' + proceso.stderr[-1500:])
    resultado = json.loads(proceso.stdout)
    if (resultado.get('estado') != 'PASS_PRODUCTO_PRECIO'
            or resultado.get('casos') != registro['casos']
            or resultado.get('casos_ejecutados') != registro['casos_previstos']
            or resultado.get('aserciones') != registro['aserciones_previstas']
            or resultado.get('sin_bd_ni_pac') is not True):
        raise RuntimeError('No se acreditó el contrato de casos y aserciones.')
    if fuentes != {n: hashlib.sha256((root / n).read_bytes()).hexdigest() for n in fuentes}:
        raise RuntimeError('Cambió una fuente durante la prueba.')
    resultado['fuentes_sha256'] = fuentes
    print(json.dumps(resultado, indent=2, ensure_ascii=False))
    print('PASS_PRODUCTO_PRECIO')
except (OSError, ValueError, KeyError, RuntimeError, subprocess.TimeoutExpired) as error:
    print('Regresión de precio bloqueada: ' + str(error), file=sys.stderr)
    sys.exit(1)
