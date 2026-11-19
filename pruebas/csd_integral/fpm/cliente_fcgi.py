"""Cliente exclusivamente Unix y namespace efímero; nunca usa red o sesiones reales."""
import argparse
import os
from pathlib import Path
import re
import stat
import subprocess
import sys

parser = argparse.ArgumentParser()
parser.add_argument('--script', choices=['sonda.php', 'alta_controlador.php'], required=True)
parser.add_argument('--body', type=Path)
parser.add_argument('--content-type', default='application/octet-stream')
args = parser.parse_args()
exterior = os.environ.get('QA_HOST_NET_NS')
assert exterior and os.readlink('/proc/self/ns/net') != exterior
assert not (Path('/home').exists() and any(Path('/home').iterdir())) and not Path('/etc/mysql').exists()
assert re.fullmatch('[a-f0-9]{32}', Path('/qa-instance-id').read_text().strip())
socket = Path('/run/php-fpm/qa.sock')
assert not socket.is_symlink() and stat.S_ISSOCK(socket.stat().st_mode)
script = Path('/var/www/html/facturacion/pruebas/csd_integral/fpm') / args.script
assert script.is_file() and not script.is_symlink()
body = b''
if args.body:
    path = args.body.resolve(strict=True)
    assert path.is_relative_to('/tmp/fpm-qa') and not args.body.is_symlink()
    assert path.stat().st_size <= 8 * 1024 * 1024
    body = path.read_bytes()
assert '\r' not in args.content_type and '\n' not in args.content_type
entorno = {
    'PATH': '/usr/bin',
    'GATEWAY_INTERFACE': 'CGI/1.1',
    'SERVER_PROTOCOL': 'HTTP/1.1',
    'SERVER_NAME': 'qa.invalid',
    'SERVER_PORT': '80',
    'REMOTE_ADDR': '127.0.0.1',
    'REQUEST_METHOD': 'POST' if args.body else 'GET',
    'SCRIPT_FILENAME': str(script),
    'SCRIPT_NAME': '/qa/' + args.script,
    'REQUEST_URI': '/qa/' + args.script,
    'DOCUMENT_ROOT': '/var/www/html/facturacion',
    'CONTENT_TYPE': args.content_type,
    'CONTENT_LENGTH': str(len(body)),
    'REDIRECT_STATUS': '200',
}
resultado = subprocess.run(['/usr/bin/cgi-fcgi', '-bind', '-connect', str(socket)],
    env=entorno, input=body, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=40)
sys.stdout.buffer.write(resultado.stdout)
sys.stderr.buffer.write(resultado.stderr)
sys.exit(resultado.returncode)
