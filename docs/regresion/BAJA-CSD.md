# Baja de certificados de sello digital

La baja física se realiza en fc_csd::elimina_bd. Una sola transacción retira el
CSD, sus vínculos CER/KEY y sus dos vínculos PEM, así como las etapas que
pertenecen exclusivamente a ese CSD. No se eliminan archivos físicos,
versiones ni filas de doc_documento.

La baja se rechaza si el CSD está asignado en la configuración de nómina o
tiene alguna referencia fuera de ese conjunto. Se comprueban las claves
foráneas y las relaciones convencionales, incluidas factura, nota de crédito
y complemento de pago. No se ejecutan bajas automáticas de dependencias del
ORM. Si un paso falla, se revierte todo. Si el llamador ya abrió una
transacción, se usa un punto de restauración. No cambian la configuración de
nómina, el PAC ni los documentos fiscales.

## Regresión

La suite de pruebas/csd_baja utiliza tablas y datos ficticios con el binario
MariaDB del laboratorio acreditado, un socket privado y red desactivada.

1. Define QA_CSD_ENGINE_ROOT con la raíz del motor verificado.
2. Ejecuta python3 pruebas/csd_baja/ejecutar.py para validar el alcance.
3. Ejecuta python3 pruebas/ejecutar_completa.py con los requisitos de las
   demás suites descritos en [CSD integral](../../pruebas/csd_integral/README.md).

La prueba focal verifica el retiro de vínculos y etapas propios, la
conservación de documentos compartidos, el bloqueo por uso de negocio o
nómina y la reversión de operaciones parciales. Usa un delegado controlado
para la baja padre; no sustituye una prueba del ORM real ni de interfaz.

En Facturación 3 se comprobó desde la interfaz, con un CSD sintético y un
respaldo recuperable, que solo desaparecen el CSD y sus relaciones previstas.
Se conservaron documentos y archivos. No se timbró ni se utilizaron
certificados reales.
