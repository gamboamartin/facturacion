#!/usr/bin/env python3
"""Contratos de descarga y migración en metadata MySQL de QA independiente y vacía."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys

root = Path(__file__).resolve().parents[2]
registro = json.loads(Path(__file__).with_name('registro.json').read_text())
env = dict(os.environ)
try:
    secret = env.get('QA_DESCARGAS_SECRET_FILE') or env.get('QA_PRODUCTOS_SECRET_FILE')
    if secret:
        cfg = json.loads(Path(secret).read_text())
        env.update(QA_DESCARGAS_DSN=cfg['dsn'], QA_DESCARGAS_DB_USER=cfg['user'], QA_DESCARGAS_DB_PASSWORD=cfg['password'])
    if not (env.get('QA_DESCARGAS_DSN') or env.get('QA_PRODUCTOS_DSN')):
        raise RuntimeError('Falta QA_DESCARGAS_SECRET_FILE o conexión de metadata efímera.')
    sha = lambda n: hashlib.sha256((root / n).read_bytes()).hexdigest()
    fuentes = {n: sha(n) for n in registro['fuentes']}
    casos = []
    for script, marker in [('controladores.php', 'PASS_DESCARGAS_CONTROLADORES'), ('permisos.php', 'PASS_DESCARGAS_PERMISOS')]:
        p = subprocess.run(['php', '-d', 'display_errors=stderr', '-d', 'zend.exception_ignore_args=1',
                            '-d', 'xdebug.mode=off', str(Path(__file__).with_name(script))],
                           cwd=root, env=env, capture_output=True, text=True, timeout=120)
        if p.returncode or p.stderr:
            raise RuntimeError('No se acreditó ' + script + ': ' + p.stderr[-2200:])
        r = json.loads(p.stdout)
        if r.get('estado') != marker:
            raise RuntimeError('Marcador incorrecto: ' + script)
        if script == 'permisos.php' and (r.get('motor_real') is not True or r.get('limpieza_confirmada') is not True or r.get('aserciones') != 45):
            raise RuntimeError('Metadata sin prueba real, limpieza o aserciones completas.')
        casos.extend(r['casos'])
    p = subprocess.run(['php', '-d', 'display_errors=stderr', '-d', 'xdebug.mode=off',
                        str(Path(__file__).with_name('controladores.php')), '--stream'],
                       cwd=root, capture_output=True, timeout=15)
    if p.returncode or p.stderr or p.stdout != b'%PDF-fixture':
        raise RuntimeError('El controlador agregó HTML/error al PDF descargado.')
    casos.append('respuesta_binaria_sin_html')
    if casos != registro['casos'] or len(casos) != registro['casos_previstos']:
        raise RuntimeError('Registro incompleto de descargas.')
    if fuentes != {n: sha(n) for n in fuentes}:
        raise RuntimeError('Una fuente cambió durante las pruebas.')
    print(json.dumps({'estado': 'PASS_DESCARGAS_DOCUMENTOS', 'casos': casos, 'casos_ejecutados': len(casos),
                      'fuentes_sha256': fuentes, 'sin_pac': True, 'metadata_real_y_limpieza_confirmada': True}, ensure_ascii=False))
    print('PASS_DESCARGAS_DOCUMENTOS')
except (OSError, ValueError, KeyError, RuntimeError, subprocess.TimeoutExpired) as error:
    print('Descargas bloqueadas: ' + str(error), file=sys.stderr)
    sys.exit(1)
