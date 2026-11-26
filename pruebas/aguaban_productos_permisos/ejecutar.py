#!/usr/bin/env python3
"""Regresión transaccional de permisos en un esquema MySQL vacío y limitado de QA."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys

root = Path(__file__).resolve().parents[2]
env = dict(os.environ)
try:
    secret_file = env.get('QA_PRODUCTOS_SECRET_FILE')
    if secret_file:
        secret = json.loads(Path(secret_file).read_text())
        env.update(QA_PRODUCTOS_DSN=secret['dsn'], QA_PRODUCTOS_DB_USER=secret['user'],
                   QA_PRODUCTOS_DB_PASSWORD=secret['password'])
    if not env.get('QA_PRODUCTOS_DSN') or not env.get('QA_PRODUCTOS_DB_USER'):
        raise RuntimeError('Falta el laboratorio independiente QA_PRODUCTOS_SECRET_FILE o QA_PRODUCTOS_DSN/DB_USER.')
    registro = json.loads(Path(__file__).with_name('registro.json').read_text())
    fuentes = {n: hashlib.sha256((root / n).read_bytes()).hexdigest() for n in registro['fuentes']}
    proceso = subprocess.run(['php', '-d', 'display_errors=stderr', '-d', 'log_errors=0',
                              str(Path(__file__).with_name('probar.php'))],
                             env=env, cwd=root, capture_output=True, text=True, timeout=120)
    if proceso.returncode or proceso.stderr:
        raise RuntimeError('PHP no acreditó la regresión: ' + proceso.stderr[-1500:])
    resultado = json.loads(proceso.stdout)
    if (resultado.get('estado') != 'PASS_AGUABAN_PRODUCTOS_PERMISOS'
            or resultado.get('casos') != registro['casos']
            or resultado.get('casos_ejecutados') != len(registro['casos'])
            or resultado.get('aserciones') != registro['aserciones_previstas']
            or resultado.get('limpieza_confirmada') is not True
            or resultado.get('motor_real') is not True):
        raise RuntimeError('No se acreditó el contrato de casos, aserciones y limpieza.')
    if fuentes != {n: hashlib.sha256((root / n).read_bytes()).hexdigest() for n in fuentes}:
        raise RuntimeError('Cambió una fuente durante la prueba.')
    resultado['fuentes_sha256'] = fuentes
    print(json.dumps(resultado, indent=2, ensure_ascii=False))
    print('PASS_AGUABAN_PRODUCTOS_PERMISOS')
except (OSError, ValueError, KeyError, RuntimeError, subprocess.TimeoutExpired) as error:
    print('Regresión de permisos bloqueada: ' + str(error), file=sys.stderr)
    sys.exit(1)
