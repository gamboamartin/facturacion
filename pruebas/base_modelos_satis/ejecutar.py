#!/usr/bin/env python3
"""Contrato de base_modelos y consumidores reales, sin config, autoload de aplicación ni BD."""
import hashlib
import json
import os
import re
from pathlib import Path
import subprocess
import sys

raiz = Path(__file__).resolve().parents[2]
vendor = Path(os.environ.get('QA_BASE_MODELOS_VENDOR', os.environ.get('QA_VENDOR', raiz / 'vendor'))).resolve()
try:
    instalados = json.loads((vendor / 'composer/installed.json').read_text())
    paquetes = {p['name']: p for p in instalados['packages']}
    evidencia = {}
    for paquete in ['base_modelos', 'administrador', 'system', 'validacion', 'errores', 'test']:
        manifiesto = json.loads((vendor / 'gamboa.martin' / paquete / 'composer.json').read_text())
        nombre = 'gamboa.martin/' + paquete
        if nombre not in paquetes:
            raise RuntimeError('Falta la identidad instalada de: ' + nombre)
        evidencia[nombre] = {
            'version': paquetes[nombre]['version'],
            'referencia': (paquetes[nombre].get('source', {}).get('reference')
                           or paquetes[nombre].get('dist', {}).get('reference')),
            'composer_sha256': hashlib.sha256((vendor / 'gamboa.martin' / paquete / 'composer.json').read_bytes()).hexdigest(),
        }
        if manifiesto.get('name') != nombre:
            raise RuntimeError('El manifiesto no corresponde al paquete esperado: ' + paquete)
    for archivo in ['composer/ClassLoader.php', 'composer/autoload_psr4.php']:
        if not (vendor / archivo).is_file():
            raise RuntimeError('Falta el mapa/autoloader de Composer de la copia QA.')
    proceso = subprocess.run(['php', '-d', 'display_errors=stderr', '-d', 'log_errors=0',
                              str(Path(__file__).with_name('probar_contrato.php')), str(vendor)],
                             cwd=Path(__file__).parent, timeout=45, check=False, capture_output=True, text=True)
    print(proceso.stdout, end='')
    print(proceso.stderr, end='', file=sys.stderr)
    registro = json.loads(Path(__file__).with_name('registro.json').read_text())
    casos = re.findall(r'^PASS: (\S+) \(\d+ assertions\)$', proceso.stdout, re.MULTILINE)
    cierre = re.findall(r'^PASS base_modelos_satis: (\d+) casos, (\d+) assertions\.$', proceso.stdout, re.MULTILINE)
    if proceso.returncode == 0 and (casos != registro['casos'] or len(cierre) != 1
                                  or int(cierre[0][0]) != registro['casos_previstos']):
        raise RuntimeError('PHP terminó sin acreditar todos los casos y el cierre registrado.')
    if proceso.returncode == 0:
        print(json.dumps({'dependencias': evidencia}, ensure_ascii=False, sort_keys=True))
        print('PASS_BASE_MODELOS_SATIS')
    sys.exit(proceso.returncode)
except (OSError, ValueError, RuntimeError, subprocess.TimeoutExpired) as error:
    print(f'Contrato base_modelos bloqueado: {error}', file=sys.stderr)
    sys.exit(1)
