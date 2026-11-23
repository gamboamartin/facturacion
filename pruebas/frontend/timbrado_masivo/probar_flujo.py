"""Render y flujo real de navegador contra respuestas ficticias; sin aplicación, BD ni PAC."""
from html.parser import HTMLParser
from pathlib import Path
import json
import os
import re
import shutil
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[3]
VIEW = ROOT / "views/fc_factura/timbrado_masivo.php"
SCRIPT = ROOT / "js/fc_factura/timbrado_masivo.js"
STYLE = ROOT / "css/fc_factura/timbrado_masivo.css"
HERE = Path(__file__).resolve().parent


class Documento(HTMLParser):
    def __init__(self):
        super().__init__()
        self.tags = []

    def handle_starttag(self, tag, attrs):
        self.tags.append((tag, dict(attrs)))


def render(config=None):
    data = {
        "endpoint": "index.php?seccion=fc_factura&accion=timbrado_masivo&session_id=ficticia",
        "csrf": "csrf-ficticio",
        "maximo": 100,
        "link_lista": "index.php?seccion=fc_factura&accion=lista",
        "emisores": [{"id": 1, "nombre": "Emisor ficticio"}],
        **(config or {}),
    }
    php = "$controlador=(object)['timbrado_masivo_config'=>json_decode(stream_get_contents(STDIN),true)];include $argv[1];"
    result = subprocess.run(
        ["php", "-d", "display_errors=stderr", "-d", "error_reporting=32767", "-r", php, str(VIEW)],
        input=json.dumps(data), capture_output=True, text=True, check=True,
    )
    if result.stderr:
        raise AssertionError(result.stderr)
    return result.stdout


class TimbradoMasivoFrontend(unittest.TestCase):
    def test_render_campos_etiquetas_y_controles(self):
        html = render()
        doc = Documento()
        doc.feed(html)
        fields = {attrs["name"]: attrs for tag, attrs in doc.tags if tag in {"input", "select"} and "name" in attrs}
        self.assertEqual(set(fields), {
            "fecha_inicio", "fecha_fin", "serie", "folio", "rfc", "cliente", "emisor_id", "total_min", "total_max",
        })
        labels = {attrs["for"] for tag, attrs in doc.tags if tag == "label" and "for" in attrs}
        self.assertTrue(all(attrs["id"] in labels for attrs in fields.values()))
        self.assertIn('id="tm-pagina-todas"', html)
        self.assertIn('id="tm-todas"', html)
        self.assertIn('id="tm-confirmacion"', html)
        self.assertIn('id="tm-resultados"', html)
        self.assertEqual(fields["fecha_inicio"].get("value", ""), "")
        self.assertEqual(fields["fecha_fin"].get("value", ""), "")

    def test_config_y_emisores_no_inyectan_html(self):
        attack = '</script><script>window.inyectado=1</script><img src=x onerror="window.inyectado=2">'
        html = render({"csrf": attack, "endpoint": attack, "emisores": [
            {"id": 1, "nombre": attack}, {"id": attack, "nombre": "inválido"}, {"id": 0, "nombre": "cero"},
        ]})
        config = re.search(r'<script type="application/json" id="tm-config">(.*?)</script>', html, re.S)
        self.assertEqual(json.loads(config.group(1))["csrf"], attack)
        self.assertNotIn(attack, html)
        doc = Documento()
        doc.feed(html)
        self.assertEqual([attrs for tag, attrs in doc.tags if tag == "script"], [{"type": "application/json", "id": "tm-config"}])
        self.assertFalse(any("onerror" in attrs for _, attrs in doc.tags))
        self.assertEqual([attrs["value"] for tag, attrs in doc.tags if tag == "option"], ["", "1"])

    def test_link_lista_rechaza_esquema_ejecutable(self):
        for link in ["javascript:alert(1)", "java\nscript:alert(1)", " data:text/html,x"]:
            doc = Documento()
            doc.feed(render({"link_lista": link}))
            links = [attrs["href"] for tag, attrs in doc.tags if tag == "a"]
            self.assertEqual(links, ["index.php?seccion=fc_factura&accion=lista"])

    def test_recursos_locales_sin_doble_carga_del_script(self):
        doc = Documento()
        doc.feed(render())
        self.assertEqual(
            [attrs["href"] for tag, attrs in doc.tags if tag == "link"],
            ["css/fc_factura/timbrado_masivo.css?v=1"],
        )
        self.assertFalse(any(tag == "script" and "src" in attrs for tag, attrs in doc.tags))

    def test_css_limitado_a_la_vista(self):
        css = STYLE.read_text()
        for selector in re.findall(r"([^{}]+)\{", css):
            selector = selector.strip()
            if selector.startswith("@media"):
                continue
            for part in selector.split(","):
                self.assertTrue(part.strip().startswith("#vista_fc_factura_timbrado_masivo"), selector)

    def test_flujos_reales_del_navegador_sin_red(self):
        node_candidates = [
            os.environ.get("NODE_BIN"),
            shutil.which("node"),
            "/home/em3/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node",
            "/usr/lib/chatgpt/resources/cua_node/bin/node",
        ]
        node = next((path for path in node_candidates if path and Path(path).is_file()), None)
        self.assertIsNotNone(node, "Se requiere Node o NODE_BIN.")
        result = subprocess.run(
            [node, str(HERE / "probar_navegador.js")],
            input=json.dumps({"html": render(), "js": SCRIPT.read_text(), "css": STYLE.read_text()}),
            capture_output=True, text=True, timeout=120,
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        data = json.loads(result.stdout)
        self.assertEqual(data["estado"], "PASS_TIMBRADO_MASIVO_UI")
        self.assertEqual(len(data["casos"]), 11)
        self.assertEqual(data["solicitudes_externas"], 0)


if __name__ == "__main__":
    unittest.main()
