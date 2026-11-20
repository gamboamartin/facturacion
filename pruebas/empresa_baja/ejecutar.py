#!/usr/bin/env python3
"""Regresión de baja de empresa: motor privado, fixtures sintéticos, sin red."""
from pathlib import Path
import os
import signal
import subprocess
import sys

proceso = subprocess.Popen(['/bin/bash', str(Path(__file__).with_name('ejecutar.sh'))], start_new_session=True)
try:
    codigo = proceso.wait(timeout=180)
except subprocess.TimeoutExpired:
    os.killpg(proceso.pid, signal.SIGTERM)
    try:
        proceso.wait(timeout=10)
    except subprocess.TimeoutExpired:
        os.killpg(proceso.pid, signal.SIGKILL)
        proceso.wait()
    print('Baja empresa: excedido el tiempo del laboratorio.', file=sys.stderr)
    sys.exit(1)
sys.exit(codigo)
