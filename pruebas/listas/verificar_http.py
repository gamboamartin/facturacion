#!/usr/bin/env python3
"""Comprueba GET get_data de cuatro listas; nunca persiste sesiones ni filas.

FACTURACION_QA_URL: raíz absoluta de aplicación (http://localhost:81/facturacion/).
FACTURACION_QA_SESSION: sesión local ya autenticada, obtenida fuera del informe.
--output /tmp/<archivo-nuevo>.json: opcional; stdout siempre contiene resumen seguro.

search[value] es soportado por datatables/filtros::genera_filtro_especial_datatable.
Usa los filtros declarados por el controlador; columns solo replica el contrato
DataTables, no decide los campos de búsqueda. El framework actual devuelve el
mismo n_registros en recordsTotal/recordsFiltered: no se exige distinción global.
Solo consulta accion=get_data; no ejecuta altas, modificaciones ni acciones PAC.
"""
from __future__ import annotations

import argparse
import datetime
import json
import os
from pathlib import Path
import sys
import urllib.error
import urllib.parse
import urllib.request
import uuid

COLUMNS = {
    "fc_factura": ["fc_factura_id", "fc_factura_folio", "com_cliente_razon_social",
                   "com_cliente_rfc", "fc_factura_fecha", "fc_factura_total",
                   "fc_factura_monto_saldo_aplicado", "fc_factura_saldo",
                   "fc_factura_folio_fiscal", "fc_factura_etapa"],
    "fc_nota_credito": ["fc_nota_credito_id", "fc_nota_credito_folio", "com_cliente_razon_social",
                        "com_cliente_rfc", "fc_nota_credito_fecha", "fc_nota_credito_total",
                        "fc_nota_credito_monto_saldo_aplicado", "fc_nota_credito_saldo",
                        "fc_nota_credito_folio_fiscal", "fc_nota_credito_etapa"],
    "fc_complemento_pago": ["fc_complemento_pago_id", "fc_complemento_pago_folio",
                           "com_cliente_razon_social", "com_cliente_rfc",
                           "fc_complemento_pago_fecha", "fc_complemento_pago_total_pagos",
                           "fc_complemento_pago_uuid", "fc_complemento_pago_etapa"],
    "fc_layout_nom": ["fc_layout_nom_id", "fc_layout_nom_codigo", "com_cliente_razon_social",
                      "com_cliente_rfc", "fc_layout_nom_descripcion", "fc_layout_nom_estado_timbrado",
                      "fc_layout_nom_estado_layout", "fc_layout_nom_fecha_pago"],
}
LENGTH = 10
MAX_BODY = 8 * 1024 * 1024


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        # No reenviar session_id a otro destino ni interpretar login como éxito.
        return None


class CheckFailure(Exception):
    """Código estable sin URLs, errores SQL ni valores de registros."""


def exigir(condition: bool, code: str) -> None:
    if not condition:
        raise CheckFailure(code)


def validar(payload: object, draw: int, start: int, empty: bool) -> dict:
    exigir(isinstance(payload, dict), "json_objeto_requerido")
    exigir(not payload.get("error") and not payload.get("sError"), "backend_reporta_error")
    exigir(all(key in payload for key in ("draw", "recordsTotal", "recordsFiltered", "data")),
           "contrato_incompleto")
    exigir(type(payload["draw"]) is int and payload["draw"] == draw, "draw_no_coincide")
    total, filtered, rows = payload["recordsTotal"], payload["recordsFiltered"], payload["data"]
    exigir(type(total) is int and type(filtered) is int, "conteos_no_enteros")
    exigir(0 <= filtered <= total, "conteos_incoherentes")
    exigir(isinstance(rows, list) and len(rows) <= LENGTH, "pagina_invalida")
    exigir(all(isinstance(row, dict) for row in rows), "fila_no_objeto")
    exigir(len(rows) <= max(0, filtered - start), "filas_exceden_conteo")
    exigir(len(rows) == min(LENGTH, max(0, filtered - start)), "pagina_incompleta")
    if filtered == 0 or start >= filtered:
        exigir(rows == [], "pagina_debe_estar_vacia")
    if empty:
        exigir(filtered == 0 and rows == [], "busqueda_sin_coincidencias_no_vacia")
    return {"draw": draw, "start": start, "length": LENGTH,
            "recordsTotal": total, "recordsFiltered": filtered, "filas_recibidas": len(rows)}


def request_case(opener, endpoint: str, session: str, section: str,
                 columns: list[str], draw: int, start: int, search: str) -> dict:
    fields = {"seccion": section, "accion": "get_data", "session_id": session,
              "ws": "1", "draw": str(draw), "start": str(start), "length": str(LENGTH),
              "search[value]": search, "search[regex]": "false"}
    for index, column in enumerate(columns):
        fields.update({f"columns[{index}][data]": column, f"columns[{index}][name]": "",
                       f"columns[{index}][searchable]": "true",
                       f"columns[{index}][orderable]": "true",
                       f"columns[{index}][search][value]": "",
                       f"columns[{index}][search][regex]": "false"})
    request = urllib.request.Request(endpoint + "?" + urllib.parse.urlencode(fields),
                                     headers={"Accept": "application/json", "X-Requested-With": "XMLHttpRequest"})
    try:
        with opener.open(request, timeout=30) as response:
            exigir(response.status == 200, "http_no_200")
            exigir(response.headers.get_content_type() == "application/json", "content_type_no_json")
            raw = response.read(MAX_BODY + 1)
            exigir(len(raw) <= MAX_BODY, "respuesta_supera_limite")
    except urllib.error.HTTPError as error:
        raise CheckFailure("http_" + str(error.code)) from None
    except (urllib.error.URLError, TimeoutError, OSError):
        raise CheckFailure("conexion_no_disponible") from None
    try:
        payload = json.loads(raw.decode("utf-8"))
    except (UnicodeError, ValueError):
        raise CheckFailure("json_invalido") from None
    summary = validar(payload, draw, start, bool(search))
    # DataTables requiere cada alias declarado: JSON válido por sí solo no evita
    # "Requested unknown parameter" al renderizar una fila.
    exigir(all(all(column in row for column in columns) for row in payload["data"]),
           "alias_de_columna_ausente")
    return summary


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--output", help="Informe nuevo bajo /tmp; nunca incluye URL, sesión ni filas")
    args = parser.parse_args()
    report = {"estado": "BLOQUEADO", "fecha_utc": datetime.datetime.now(datetime.timezone.utc).isoformat(),
              "alcance": "GET get_data de listas; no acredita UI, PAC ni mutaciones",
              "casos": []}
    try:
        base = os.environ.get("FACTURACION_QA_URL", "")
        session = os.environ.get("FACTURACION_QA_SESSION", "")
        exigir(bool(base) and bool(session), "variables_entorno_requeridas")
        parts = urllib.parse.urlsplit(base)
        exigir(parts.scheme in ("http", "https") and bool(parts.hostname)
               and not parts.username and not parts.password and not parts.query and not parts.fragment,
               "url_base_invalida")
        # Esta herramienta verifica el despliegue local expresamente, sin enviar sesión a servidores externos.
        exigir(parts.hostname in ("localhost", "127.0.0.1", "::1"), "destino_debe_ser_local")
        endpoint = urllib.parse.urljoin(base.rstrip("/") + "/", "index.php")
        opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect())
        impossible = "QA_SIN_COINCIDENCIAS_" + uuid.uuid4().hex
        for section, columns in COLUMNS.items():
            for name, draw, start, search in (("primera_pagina", 1, 0, ""),
                                              ("pagina_siguiente", 2, 10, ""),
                                              ("busqueda_sin_coincidencias", 3, 0, impossible)):
                case = {"seccion": section, "caso": name, "estado": "BLOQUEADO"}
                try:
                    case.update(request_case(opener, endpoint, session, section, columns, draw, start, search))
                    case["estado"] = "PASS"
                except CheckFailure as error:
                    case["motivo"] = str(error)
                report["casos"].append(case)
        report["casos_esperados"] = len(COLUMNS) * 3
        report["casos_aprobados"] = sum(case["estado"] == "PASS" for case in report["casos"])
        if report["casos_aprobados"] == report["casos_esperados"]:
            report["estado"] = "PASS_LISTAS_HTTP"
    except CheckFailure as error:
        report["motivo"] = str(error)
    except Exception:
        # Nunca serializar excepciones externas: pueden contener URL de petición o datos de respuesta.
        report["motivo"] = "error_interno_verificador"
    serialized = json.dumps(report, ensure_ascii=False, indent=2) + "\n"
    if args.output:
        try:
            output = Path(args.output)
            exigir(output.is_absolute() and output.parent.resolve().is_relative_to(Path("/tmp")),
                   "informe_debe_estar_bajo_tmp")
            # No sobrescribir archivos previos ni seguir enlaces simbólicos de archivo final.
            fd = os.open(output, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
            with os.fdopen(fd, "w", encoding="utf-8") as stream:
                stream.write(serialized)
        except Exception:
            report["estado"] = "BLOQUEADO"
            report["motivo_informe"] = "no_se_pudo_crear_informe_nuevo_bajo_tmp"
            serialized = json.dumps(report, ensure_ascii=False, indent=2) + "\n"
    sys.stdout.write(serialized)
    return 0 if report["estado"] == "PASS_LISTAS_HTTP" else 1


if __name__ == "__main__":
    sys.exit(main())
