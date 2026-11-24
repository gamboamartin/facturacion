# Complemento de pago Aguaban: render real

Ejecutar desde una copia aislada con las dependencias de aplicación instaladas y Poppler/Pillow disponibles:

```sh
python3 pruebas/pdf_complemento_aguaban/ejecutar.py
```

`QA_CSD_DEPS` permite indicar otra copia que contiene `vendor/`. La suite clona esas dependencias en un directorio privado y pasa un directorio temporal explícito a mPDF; no carga la configuración privada de la aplicación. La copia temporal de vendor se elimina al terminar. Los PDF, PNG, entradas ficticias y el informe se conservan en `.git/qa/pdf-complemento-aguaban/` y `pruebas/resultados/pdf_complemento_aguaban.json`. `QA_PDF_COMPLEMENTO_OUTPUT` puede fijar un directorio de salida nuevo y vacío.

El registro define seis casos: un pago y un documento, dos pagos y 24 documentos, sellos largos y nombres Unicode, comprobante pendiente, texto parecido a HTML y XML Pagos 2.0 → proveedor real → PDF. Comprueba contenido completo, serie/folio y UUID de cada documento, importes/parcialidad, fechas independientes, sellos/cadena sin recortes, páginas A4 y cajas de texto sin solapamientos. La plantilla importada conserva texto invisible debajo de las áreas blancas; se descuenta por contenido y coordenadas para comprobar la geometría dinámica. La inspección visual sigue siendo una comprobación adicional: las cajas de texto no detectan líneas decorativas mal situadas.

El QR se genera mediante la biblioteca PHP de aplicación; no requiere red. El módulo Python `zxingcpp` es un recurso obligatorio de esta suite: el QR se decodifica desde las páginas rasterizadas y se exige destino SAT, UUID/RFC, total del comprobante `0.000000` y los ocho caracteres finales del sello. Un pendiente debe carecer de QR. `QA_ZXING_LIB` permite indicar una instalación aislada del módulo; por ejemplo:

```sh
python3 -m pip install --target /tmp/qa-pdf-zxing -r pruebas/pdf_complemento_aguaban/requirements.txt
QA_ZXING_LIB=/tmp/qa-pdf-zxing python3 pruebas/pdf_complemento_aguaban/ejecutar.py
```

Si falta ese módulo, la suite queda bloqueada y devuelve código distinto de cero. La presencia/ausencia de imagen también se comprueba. Los datos son ficticios y no constituyen un CFDI válido. La suite no conecta bases de datos, no accede al PAC, no envía correo ni regenera documentos de clientes. No certifica firmas, certificados o aceptación fiscal por el SAT.
