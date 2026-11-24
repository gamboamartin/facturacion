"""Regresión aislada de captura y confirmación de complemento desde factura."""
from html.parser import HTMLParser
from pathlib import Path
import json
import os
import re
import shutil
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[3]
HERE = Path(__file__).resolve().parent


def configuracion():
    return {
        "endpoint": "index.php?seccion=fc_factura&accion=complemento_pago&registro_id=7",
        "link_lista": "index.php?seccion=fc_factura&accion=lista",
        "csrf": "csrf-ficticio", "clave_solicitud": "a" * 32,
        "factura": {"id": 7, "folio": "F-000007", "uuid": "00000000-0000-4000-8000-000000000007",
                    "emisor": "Emisor ficticio", "cliente": "Cliente ficticio", "moneda": "MXN",
                    "saldo": "116.00", "parcialidad": 2},
        "valores": {"monto": "116.00", "fecha_pago": "", "cat_sat_forma_pago_id": "",
                    "com_tipo_cambio_id": 1, "fc_csd_id": 3},
        "formas_pago": [{"id": 1, "codigo": "01", "descripcion": "Efectivo"},
                        {"id": 3, "codigo": "03", "descripcion": "Transferencia"},
                        {"id": 99, "codigo": "99", "descripcion": "Por definir"}],
        "tipos_cambio": [{"id": 1, "moneda_id": 1, "moneda": "MXN", "fecha": "2026-11-24", "monto": "1.00"},
                         {"id": 2, "moneda_id": 2, "moneda": "USD", "fecha": "2026-11-24", "monto": "20.00"},
                         {"id": 4, "moneda_id": 2, "moneda": "USD", "fecha": "2026-11-23", "monto": "19.50"}],
        "csds": [{"id": 3, "descripcion": "Certificado ficticio vigente"}],
    }


def render(datos=None):
    result = subprocess.run(
        ["php", "-d", "display_errors=stderr", "-d", "error_reporting=32767", str(HERE / "renderizar.php")],
        input=json.dumps(datos if datos is not None else configuracion()), text=True, capture_output=True, check=True,
    )
    if result.stderr:
        raise AssertionError(result.stderr)
    return result.stdout


class Documento(HTMLParser):
    def __init__(self):
        super().__init__()
        self.tags = []

    def handle_starttag(self, tag, attrs):
        self.tags.append((tag, dict(attrs)))


class ComplementoDesdeFacturaFrontend(unittest.TestCase):
    def test_precarga_campos_reales_y_referencia_sin_campos_fiscales_editables(self):
        html = render()
        doc = Documento()
        doc.feed(html)
        campos = {a["name"]: a for tag, a in doc.tags if tag in {"input", "select"} and "name" in a}
        self.assertEqual(set(campos), {"fc_csd_id", "monto", "fecha_pago", "cat_sat_forma_pago_id", "com_tipo_cambio_id"})
        self.assertEqual(campos["monto"]["value"], "116.00")
        self.assertEqual(campos["fecha_pago"]["value"], "")
        labels = {a.get("for") for tag, a in doc.tags if tag == "label"}
        for campo in campos.values():
            self.assertIn(campo["id"], labels)
            self.assertIn("required", campo)
        self.assertIn("F-000007", html)
        self.assertNotIn('value="99"', html)
        self.assertNotIn('name="uuid"', html)
        self.assertNotIn('name="num_parcialidad"', html)
        self.assertIn("Al guardar se registrará el pago y se actualizará el saldo de la factura.", html)
        self.assertIn("El complemento quedará pendiente de timbrado.", html)

    def test_escapa_datos_de_referencia_catalogos_y_json(self):
        attack = '</script><img src=x onerror="window.inyectado=1">'
        datos = configuracion()
        datos["factura"]["cliente"] = attack
        datos["factura"]["uuid"] = attack
        datos["csrf"] = attack
        datos["formas_pago"][0]["descripcion"] = attack
        datos["formas_pago"].append({"id": attack, "codigo": "01", "descripcion": "Inválido"})
        html = render(datos)
        self.assertNotIn(attack, html)
        self.assertIn("&lt;img", html)
        config = re.search(r'<script id="cdf-config" type="application/json">(.*?)</script>', html, re.S)
        self.assertEqual(json.loads(config[1])["csrf"], attack)
        doc = Documento()
        doc.feed(html)
        self.assertFalse(any(tag == "img" for tag, _ in doc.tags))
        self.assertFalse(any("onerror" in attrs for _, attrs in doc.tags))

    def test_link_lista_rechaza_url_ejecutable_o_fuera_de_la_aplicacion(self):
        for url in ["javascript:alert(1)", "https://otro.test/", "//otro.test/x", "index.php?x=1\\x"]:
            datos = configuracion()
            datos["link_lista"] = url
            doc = Documento()
            doc.feed(render(datos))
            self.assertEqual([a["href"] for tag, a in doc.tags if tag == "a"],
                             ["index.php?seccion=fc_factura&accion=lista"])

    def test_recursos_locales_css_encapsulado_y_sin_doble_script(self):
        doc = Documento()
        doc.feed(render())
        self.assertEqual([a["href"] for tag, a in doc.tags if tag == "link"], ["css/fc_factura/complemento_pago.css?v=1"])
        self.assertFalse(any(tag == "script" and "src" in a for tag, a in doc.tags))
        css = (ROOT / "css/fc_factura/complemento_pago.css").read_text()
        css = re.sub(r"/\*.*?\*/", "", css, flags=re.S)
        for selector in re.findall(r"([^{}]+)\{", css):
            if selector.strip().startswith("@media"):
                continue
            for parte in selector.split(",\n"):
                self.assertTrue(parte.strip().startswith("#vista_fc_factura_complemento_pago"), parte)

    def test_catalogos_ausentes_no_habilitan_guardado(self):
        datos = configuracion()
        datos["formas_pago"] = []
        datos["tipos_cambio"] = []
        html = render(datos)
        self.assertIn('data-catalogos-disponibles="0"', html)
        self.assertIn("Faltan formas de pago o tipos de cambio disponibles", html)

    def test_navegador_real_con_todas_las_respuestas_interceptadas(self):
        nodes = [os.environ.get("NODE_BIN"), shutil.which("node"),
                 "/home/em3/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node",
                 "/usr/lib/chatgpt/resources/cua_node/bin/node"]
        node = next((p for p in nodes if p and Path(p).is_file()), None)
        self.assertIsNotNone(node, "Se requiere Node o NODE_BIN")
        ataque = configuracion()
        ataque["factura"]["cliente"] = '<img src=x onerror="window.inyectado=1">'
        vacios = configuracion()
        vacios["formas_pago"] = []
        result = subprocess.run(
            [node, str(HERE / "probar_navegador.js")],
            input=json.dumps({"html": render(), "html_ataque": render(ataque), "html_vacios": render(vacios),
                              "js": (ROOT / "js/fc_factura/complemento_pago.js").read_text(),
                              "css": (ROOT / "css/fc_factura/complemento_pago.css").read_text(),
                              "css_base": (ROOT / "css/overrides.css").read_text()}),
            text=True, capture_output=True, timeout=120,
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        resultado = json.loads(result.stdout)
        registro = json.loads((HERE / "registro.json").read_text())
        self.assertEqual(resultado["estado"], "PASS_COMPLEMENTO_DESDE_FACTURA_UI")
        self.assertEqual(resultado["casos"], registro["casos_navegador"])
        self.assertEqual(resultado["solicitudes_externas"], 0)


if __name__ == "__main__":
    unittest.main()
