"""Renderiza las vistas reales con DTO ficticio; no carga autoload, configuración ni BD."""
from html.parser import HTMLParser
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[3]

class Documento(HTMLParser):
    def __init__(self, html):
        super().__init__(convert_charrefs=True)
        self.forms = []
        self.fields = []
        self.buttons = []
        self.ids = []
        self.tags = []
        self.feed(html)

    def handle_starttag(self, tag, attrs):
        attributes = dict(attrs)
        self.tags.append(tag)
        if attributes.get('id'):
            self.ids.append(attributes['id'])
        if tag == 'form':
            self.forms.append(attributes)
        if tag in {'input', 'select', 'textarea'}:
            self.fields.append(attributes)
        if tag == 'button':
            self.buttons.append(attributes)


def renderizar(accion, seccion='com_producto'):
    with tempfile.TemporaryDirectory(prefix='qa-vistas-productos-') as tmp:
        (Path(tmp) / 'mensajes.php').write_text('<?php // Sin mensajes ficticios.\n')
        process = subprocess.run(
            ['php', '-d', 'display_errors=stderr', '-d', 'log_errors=0',
             str(Path(__file__).with_name('renderizar.php')), str(ROOT), accion, seccion, tmp],
            capture_output=True, text=True, timeout=15)
    if process.returncode or process.stderr:
        raise AssertionError('La vista no pudo renderizarse limpiamente: ' + process.stderr)
    return Documento(process.stdout), process.stdout
