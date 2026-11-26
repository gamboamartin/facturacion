"""Descargas de documentos ficticios; no abre la aplicación ni invoca BD o PAC."""
from pathlib import Path
from html.parser import HTMLParser
import json
import os
import shutil
import subprocess
import unittest

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[2]
SECCIONES = ['fc_factura', 'fc_nota_credito', 'fc_complemento_pago']

def render(**datos):
    resultado = subprocess.run(['php', '-d', 'display_errors=stderr', str(HERE / 'renderizar.php')],
                               input=json.dumps({'seccion': 'fc_factura', **datos}),
                               capture_output=True, text=True, timeout=15)
    if resultado.returncode or resultado.stderr:
        raise AssertionError(resultado.stdout + resultado.stderr)
    return resultado.stdout

class Documento(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.enlaces = []
        self.scripts = []
        self.iframes = 0
        self.feed(html)
    def handle_starttag(self, tag, attrs):
        datos = dict(attrs)
        if tag == 'a': self.enlaces.append(datos)
        if tag == 'script': self.scripts.append(datos)
        if tag == 'iframe': self.iframes += 1

class DescargasSeparadas(unittest.TestCase):
    def test_tres_vistas_xml_antes_pdf_y_recurso_comun(self):
        for seccion in SECCIONES:
            doc = Documento(render(seccion=seccion))
            self.assertEqual([a['data-descarga'] for a in doc.enlaces], ['xml', 'pdf'])
            self.assertTrue(all(a['target'] == '_blank' and a['rel'] == 'noopener' for a in doc.enlaces))
            self.assertEqual(len(doc.scripts), 1)
            self.assertTrue(doc.scripts[0]['src'].startswith('js/descargas/por_separado.js?v='))
            self.assertEqual(doc.iframes, 0)

    def test_sin_enlaces_no_solicita_documentos(self):
        html = render(xml='', pdf='')
        doc = Documento(html)
        self.assertEqual(doc.enlaces, [])
        self.assertEqual(doc.scripts, [])
        self.assertIn('No hay enlaces de descarga disponibles', html)

    def test_un_permiso_no_inventa_el_otro(self):
        for ausente, presente in [('xml', 'pdf'), ('pdf', 'xml')]:
            doc = Documento(render(**{ausente: ''}))
            self.assertEqual([a['data-descarga'] for a in doc.enlaces], [presente])

    def test_urls_ajenas_ejecutables_otro_documento_o_accion_no_se_renderizan(self):
        for url in ['javascript:alert(1)', '//otro.test/index.php', 'https://otro.test/index.php',
                    './index.php?seccion=fc_factura&accion=elimina_bd&registro_id=7',
                    './index.php?seccion=fc_nota_credito&accion=descarga_xml&registro_id=7',
                    './index.php?seccion=fc_factura&accion=descarga_xml&registro_id=9',
                    './index.php?seccion=fc_factura&accion=descarga_xml&registro_id=7&registro_id=8',
                    './index.php?seccion=fc_factura&accion=descarga_xml&registro_id[]=7',
                    './index.php?seccion=fc_factura&accion=descarga_xml&registro_id=7#fragmento']:
            doc = Documento(render(xml=url, pdf=''))
            self.assertEqual(doc.enlaces, [], url)

    def test_escapa_parametros_adicionales_y_no_cierra_pestana(self):
        url = './index.php?seccion=fc_factura&accion=descarga_xml&registro_id=7&session_id="<img src=x onerror=alert(1)>'
        # Los espacios de un enlace inesperado también impiden publicarlo.
        self.assertEqual(Documento(render(xml=url, pdf='')).enlaces, [])
        url = './index.php?seccion=fc_factura&accion=descarga_xml&registro_id=7&session_id="<x>'
        html = render(xml=url, pdf='')
        self.assertIn('&quot;&lt;x&gt;', html)
        self.assertNotIn('session_id="<x>', html)
        self.assertFalse((ROOT / 'js/fc_factura/descargar_por_separado.js').exists(), 'Eliminar el JS obsoleto también durante deployment')
        for ruta in ['js/descargas/por_separado.js', 'js/fc_factura/descargar_por_separado.js.php',
                     'js/fc_complemento_pago/descargar_por_separado.js']:
            self.assertNotRegex((ROOT / ruta).read_text(), r'\bwindow\s*\.\s*close\s*\(|\bsetTimeout\s*\(')

    def test_navegador_descargas_reales_orden_errores_y_enlaces_manuales(self):
        candidatos = [os.environ.get('NODE_BIN'), shutil.which('node'),
                      '/home/em3/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node']
        node = next((p for p in candidatos if p and Path(p).is_file()), None)
        self.assertIsNotNone(node, 'Se requiere Node o NODE_BIN')
        entrada = {'vistas': {s: render(seccion=s) for s in SECCIONES},
                   'js': (ROOT / 'js/descargas/por_separado.js').read_text()}
        resultado = subprocess.run([node, str(HERE / 'probar_navegador.js')], input=json.dumps(entrada),
                                   capture_output=True, text=True, timeout=120)
        self.assertEqual(resultado.returncode, 0, resultado.stdout + resultado.stderr)
        dato = json.loads(resultado.stdout)
        self.assertEqual(dato['estado'], 'PASS_DESCARGAS_SEPARADAS_UI')
        self.assertEqual(dato['casos'], json.loads((HERE / 'registro.json').read_text())['casos_navegador'])
        self.assertEqual(dato['solicitudes_externas'], 0)

if __name__ == '__main__': unittest.main()
