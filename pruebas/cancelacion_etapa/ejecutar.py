#!/usr/bin/env python3
"""Persistencia y concurrencia reales de _etapa/cancela_bd; padre ORM y PAC ficticios."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys

try:
    raiz = Path(__file__).resolve().parents[2]
    registro = json.loads(Path(__file__).with_name('registro.json').read_text())
    sha = lambda n: hashlib.sha256((raiz / n).read_bytes()).hexdigest()
    fuentes = {n: sha(n) for n in registro['fuentes']}
    env = dict(os.environ)
    secret = env.get('QA_CANCELACION_SECRET_FILE')
    if secret:
        cfg = json.loads(Path(secret).read_text())
        env.update(QA_CANCELACION_DSN=cfg['dsn'], QA_CANCELACION_DB_USER=cfg['user'], QA_CANCELACION_DB_PASSWORD=cfg['password'])
    if not env.get('QA_CANCELACION_DSN'):
        raise RuntimeError('Falta conexión exclusiva QA_CANCELACION_SECRET_FILE o QA_CANCELACION_DSN.')
    p = subprocess.run(['php', '-d', 'display_errors=stderr', '-d', 'zend.exception_ignore_args=1',
                        str(Path(__file__).with_name('integracion_bd.php'))], env=env, capture_output=True, text=True, timeout=120)
    if p.returncode or p.stderr:
        raise RuntimeError('Integración no acreditada: ' + p.stderr[-2400:])
    resultado = json.loads(p.stdout)
    if (resultado['estado'] != 'PASS_CANCELACION_ETAPA' or resultado['limpieza_confirmada'] is not True
            or resultado.get('motor_real') is not True or resultado.get('sin_pac_real') is not True):
        raise RuntimeError('Integración o limpieza incompleta.')
    if (resultado['casos'] != registro['casos'] or len(resultado['casos']) != registro['casos_previstos']
            or len(set(resultado['casos'])) != len(resultado['casos'])):
        raise RuntimeError('Casos incompletos o distintos del registro.')
    if fuentes != {n: sha(n) for n in registro['fuentes']}:
        raise RuntimeError('Las fuentes cambiaron durante la ejecución.')
    resultado['fuentes_sha256'] = fuentes
    print(json.dumps(resultado, ensure_ascii=False))
    print('PASS_CANCELACION_ETAPA')
except (RuntimeError, OSError, ValueError, KeyError, subprocess.TimeoutExpired) as e:
    print(str(e), file=sys.stderr)
    sys.exit(1)
