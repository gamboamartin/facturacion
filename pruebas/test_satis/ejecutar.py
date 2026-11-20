#!/usr/bin/env python3
"""Contrato de gamboa.martin/test: código real, sin constructor de BD ni config activa."""
import json
import os
from pathlib import Path
import subprocess
import sys

raiz = Path(__file__).resolve().parents[2]
paquete = Path(os.environ.get('PAQUETE_TEST', raiz / 'vendor/gamboa.martin/test')).resolve()
errores = Path(os.environ.get('FUENTE_ERRORES', raiz / 'vendor/gamboa.martin/errores/src/errores.php')).resolve()
autoload = Path(os.environ.get('AUTOLOAD_PHPUNIT_AISLADO', raiz / 'pruebas/herramientas/vendor/autoload.php')).resolve()
try:
    fuentes = [paquete / 'src/liberator.php', paquete / 'src/test.php', errores, autoload]
    if not all(f.is_file() for f in fuentes):
        raise RuntimeError('Falta una fuente o el autoload de PHPUnit aislado.')
    manifiesto = json.loads((paquete / 'composer.json').read_text())
    if manifiesto.get('name') != 'gamboa.martin/test':
        raise RuntimeError('El origen no corresponde al paquete esperado.')
    proceso = subprocess.run(['php', '-d', 'display_errors=stderr', '-d', 'log_errors=0',
                              str(Path(__file__).with_name('probar_contrato.php')),
                              str(paquete), str(errores), str(autoload)],
                             cwd=Path(__file__).parent, timeout=45, check=False)
    sys.exit(proceso.returncode)
except (OSError, ValueError, RuntimeError, subprocess.TimeoutExpired) as error:
    print(f'Contrato test bloqueado: {error}', file=sys.stderr)
    sys.exit(1)
