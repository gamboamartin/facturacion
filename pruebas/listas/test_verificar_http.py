"""Contratos del verificador HTTP con respuestas sintéticas; sin red ni aplicación."""
from contextlib import redirect_stdout
from email.message import Message
import importlib.util
import io
import json
import os
from pathlib import Path
import sys
import unittest
from unittest.mock import patch
from urllib.parse import parse_qs, urlsplit

spec = importlib.util.spec_from_file_location("verificar_http", Path(__file__).with_name("verificar_http.py"))
qa = importlib.util.module_from_spec(spec)
spec.loader.exec_module(qa)


class Respuesta:
    def __init__(self, body, content_type="application/json", status=200):
        self.status = status
        self.headers = Message()
        self.headers["Content-Type"] = content_type
        self.body = body if isinstance(body, bytes) else json.dumps(body).encode()

    def __enter__(self):
        return self

    def __exit__(self, *args):
        return False

    def read(self, size):
        return self.body[:size]


class Apertura:
    def __init__(self, respuesta):
        self.respuesta = respuesta
        self.peticiones = []

    def open(self, request, timeout):
        self.peticiones.append(request)
        return self.respuesta


class VerificarHTTPTest(unittest.TestCase):
    def payload(self, **cambios):
        data = {"draw": 1, "recordsTotal": 0, "recordsFiltered": 0, "data": []}
        data.update(cambios)
        return data

    def filas_sinteticas(self, cantidad):
        return [{column: "VALOR_SINTETICO_PRIVADO" for column in qa.COLUMNS["fc_factura"]}
                for _ in range(cantidad)]

    def consultar(self, body, content_type="application/json", draw=1, start=0, search=""):
        opener = Apertura(Respuesta(body, content_type))
        result = qa.request_case(opener, "http://localhost:81/facturacion/index.php", "sesion-sintetica-secreta",
                                 "fc_factura", qa.COLUMNS["fc_factura"], draw, start, search)
        return result, opener

    def testErrorDeBackendEnHTTP200SeRechaza(self):
        # Reproduce la alerta literal '- 1': JSON de error válido sin contrato de filas.
        with self.assertRaisesRegex(qa.CheckFailure, "backend_reporta_error"):
            self.consultar({"error": 1, "mensaje": "Error al obtener data result", "data": {"sql": "no-publicar"}})

    def testExitoVacioEsValido(self):
        result, _ = self.consultar(self.payload())
        self.assertEqual(0, result["filas_recibidas"])
        self.assertEqual(0, result["recordsFiltered"])
        self.assertNotIn("data", result)

    def testDrawAjenoSeRechaza(self):
        with self.assertRaisesRegex(qa.CheckFailure, "draw_no_coincide"):
            self.consultar(self.payload(draw=99))

    def testConteosInvalidosSeRechazan(self):
        for counts in ((-1, -1), ("1", "1"), (True, True), (0, 1)):
            with self.subTest(counts=counts), self.assertRaises(qa.CheckFailure):
                self.consultar(self.payload(recordsTotal=counts[0], recordsFiltered=counts[1]))

    def testPaginaMayorQueLimiteSeRechaza(self):
        with self.assertRaisesRegex(qa.CheckFailure, "pagina_invalida"):
            self.consultar(self.payload(recordsTotal=11, recordsFiltered=11, data=[{}] * 11))

    def testFilasSinConteoSeRechazan(self):
        with self.assertRaisesRegex(qa.CheckFailure, "filas_exceden_conteo"):
            self.consultar(self.payload(data=[{"fc_factura_id": 1}]))

    def testPrimeraPaginaIncompletaSeRechaza(self):
        for recibidas in (0, 9):
            with self.subTest(recibidas=recibidas), self.assertRaisesRegex(qa.CheckFailure, "pagina_incompleta"):
                self.consultar(self.payload(recordsTotal=25, recordsFiltered=25, data=self.filas_sinteticas(recibidas)))

    def testPaginaSiguienteIncompletaSeRechaza(self):
        for recibidas in (0, 9):
            with self.subTest(recibidas=recibidas), self.assertRaisesRegex(qa.CheckFailure, "pagina_incompleta"):
                self.consultar(self.payload(recordsTotal=25, recordsFiltered=25, data=self.filas_sinteticas(recibidas)), start=10)

    def testUltimaPaginaParcialIncompletaSeRechaza(self):
        for recibidas in (0, 4):
            with self.subTest(recibidas=recibidas), self.assertRaisesRegex(qa.CheckFailure, "pagina_incompleta"):
                self.consultar(self.payload(recordsTotal=25, recordsFiltered=25, data=self.filas_sinteticas(recibidas)), start=20)

    def testPaginasCompletasYUltimasParcialesSonValidas(self):
        for total, start, recibidas in ((25, 0, 10), (25, 10, 10), (25, 20, 5),
                                       (7, 0, 7), (15, 10, 5), (20, 20, 0)):
            with self.subTest(total=total, start=start):
                rows = self.filas_sinteticas(recibidas)
                result, _ = self.consultar(self.payload(recordsTotal=total, recordsFiltered=total, data=rows),
                                          start=start)
                self.assertEqual(recibidas, result["filas_recibidas"])
                self.assertEqual(total, result["recordsFiltered"])
                self.assertNotIn("data", result)
                self.assertNotIn("VALOR_SINTETICO_PRIVADO", json.dumps(result))

    def testJSONInvalidoSeRechaza(self):
        with self.assertRaisesRegex(qa.CheckFailure, "json_invalido"):
            self.consultar(b'{"draw":1')

    def testHTMLLoginSeRechaza(self):
        with self.assertRaisesRegex(qa.CheckFailure, "content_type_no_json"):
            self.consultar(b'<html>login</html>', "text/html")

    def testBusquedaImposibleDebeQuedarVacia(self):
        with self.assertRaisesRegex(qa.CheckFailure, "busqueda_sin_coincidencias_no_vacia"):
            self.consultar(self.payload(recordsTotal=1, recordsFiltered=1, data=[{}]), search="QA_IMPOSIBLE")

    def testPeticionReplicaPaginacionYBusqueda(self):
        _, opener = self.consultar(self.payload(draw=2), draw=2, start=10, search="QA_IMPOSIBLE")
        request = opener.peticiones[0]
        params = parse_qs(urlsplit(request.full_url).query, keep_blank_values=True)
        expected = {"accion": "get_data", "ws": "1", "draw": "2", "start": "10", "length": "10",
                    "seccion": "fc_factura", "search[value]": "QA_IMPOSIBLE", "search[regex]": "false",
                    "columns[0][data]": "fc_factura_id"}
        for key, value in expected.items():
            self.assertEqual([value], params[key])
        self.assertEqual("GET", request.get_method())

    def testResumenNoExponeSesionFilasNiErroresSQL(self):
        opener = Apertura(Respuesta({"error": 1, "data": {"sql": "SQL_PRIVADO", "rfc": "RFC_PRIVADO"}}))
        output = io.StringIO()
        with patch.dict(os.environ, {"FACTURACION_QA_URL": "http://localhost:81/facturacion/",
                                      "FACTURACION_QA_SESSION": "TOKEN_PRIVADO"}), \
             patch.object(sys, "argv", ["verificar_http.py"]), \
             patch.object(qa.urllib.request, "build_opener", return_value=opener), redirect_stdout(output):
            self.assertEqual(1, qa.main())
        summary = json.loads(output.getvalue())
        self.assertEqual(12, len(summary["casos"]))
        self.assertEqual(0, summary["casos_aprobados"])
        for secret in ("TOKEN_PRIVADO", "SQL_PRIVADO", "RFC_PRIVADO", "session_id", "http://"):
            self.assertNotIn(secret, output.getvalue())

    def testFilaSinAliasesDeclaradosSeRechaza(self):
        with self.assertRaisesRegex(qa.CheckFailure, "alias_de_columna_ausente"):
            self.consultar(self.payload(recordsTotal=1, recordsFiltered=1,
                                        data=[{"fc_factura_id": 1}]))

    def testFilaCompletaEsValidaSinExponerValores(self):
        row = {column: "VALOR_SINTETICO_PRIVADO" for column in qa.COLUMNS["fc_factura"]}
        row["fc_factura_id"] = 1
        result, _ = self.consultar(self.payload(recordsTotal=1, recordsFiltered=1, data=[row]))
        self.assertEqual(1, result["filas_recibidas"])
        self.assertNotIn("data", result)
        self.assertNotIn("VALOR_SINTETICO_PRIVADO", json.dumps(result))

    def testDestinoExternoRechazadoAntesDeEnviarSesion(self):
        output = io.StringIO()
        with patch.dict(os.environ, {"FACTURACION_QA_URL": "https://externo.invalid/",
                                      "FACTURACION_QA_SESSION": "TOKEN_PRIVADO"}), \
             patch.object(sys, "argv", ["verificar_http.py"]), \
             patch.object(qa.urllib.request, "build_opener") as factory, redirect_stdout(output):
            self.assertEqual(1, qa.main())
            factory.assert_not_called()
        self.assertEqual("destino_debe_ser_local", json.loads(output.getvalue())["motivo"])


if __name__ == "__main__":
    unittest.main()
