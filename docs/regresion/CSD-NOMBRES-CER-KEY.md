# Nombres independientes de CER y KEY

## Causa y alcance

`maqueta_file` seleccionaba el `tmp_name` correcto, pero `file_name_extension` siempre consultaba el nombre de `fc_key_csd_doc`. El certificado conservaba sus bytes correctos con descripción terminada en `.key`. El ensayo ficticio CSD21 confirmó por hashes el vínculo correcto; no hubo intercambio de contenidos.

La corrección añade el parámetro `key_entidad` a `file_name_extension`, con valor predeterminado histórico `fc_key_csd_doc`. `maqueta_file` pasa su argumento. Se conserva el validador documental existente y el nombre aleatorio de 14 dígitos; no se guardan literalmente nombres originales ni se cambia la política de formatos.

## Pruebas

Nueva suite obligatoria `pruebas/csd_nombres/ejecutar.py`: ocho casos, métodos reales del controlador y dobles explícitos de constructor/validador. Comprueba CER y KEY independientes, correspondencia de tmp_name, entradas originales intactas, default histórico, longitud de salida y delegación del formato, CER sin dependencia del nombre KEY y propagación de error sin documento generado. No sustituye una prueba de subida ni certifica todos los límites del validador documental.

Batería completa: 155 pruebas unitarias / 539 aserciones, 46 focales de archivos, 3 escenarios ORM, 1 alta FPM local, 12 casos de vista, 8 de nombres y 21 frontend. Los 13 ejecutores terminaron con código 0. Expectativas previas conservadas.

Comprobación adicional PASS el 18 de noviembre de 2026 a las 21:07:44 UTC en variante independiente: controlador y constructor reales, uploads SAPI y ORM persistieron descripciones de 14 dígitos con extensión `.cer` para la relación CER y `.key` para KEY. Ambas se comprobaron con JOIN a doc_documento, sin imprimir contenidos. Motor y FPM efímeros cerrados; aislamiento verificado. PHP local 8.3.17 / OpenSSL 3.0.13. La variante conserva las aserciones previas y añade estos dos requisitos al resultado PASS; no representa navegador ni FPM de FC3. No se ha desplegado esta corrección ni revisado en navegador. No se usaron credenciales reales ni PAC.
