#!/usr/bin/env python3
"""Regresión de baja física CSD contra un esquema íntegramente ficticio."""
from pathlib import Path
import subprocess
import sys

gui = Path(__file__).resolve().with_name('ejecutar.sh')
try:
    subprocess.run(['/bin/bash', str(gui)], check=True, timeout=180)
except (subprocess.CalledProcessError, subprocess.TimeoutExpired) as error:
    print(f'Baja CSD bloqueada: {error}', file=sys.stderr)
    sys.exit(1)
