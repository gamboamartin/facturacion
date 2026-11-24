# Complemento desde factura con la cadena ORM real

Esta suite ejecuta `_complemento_desde_factura::crear()` **sin callback de creación**. Utiliza los modelos instalados `fc_complemento_pago`, `fc_pago_pago` y `fc_docto_relacionado`, incluyendo la partida de pago, el contenedor, sus totales, las etapas y la actualización del saldo.

Los cinco contratos son:

- Alta de un pago parcial y comprobación de los enlaces, importes, saldo y totales persistidos.
- Reintento de la misma solicitud sin insertar registros nuevos.
- Segundo pago con incremento de parcialidad y saldo anterior correcto.
- Factura ficticia con IVA de 16%: un pago de 58 sobre un total de 116 genera base de documento 50, impuesto 8 y los totales correspondientes.
- Fallo SQL inyectado en el alta del documento relacionado, después de la cabecera y del pago, con reversión de la estructura completa y del saldo. Se exige acreditar el mensaje del fallo inyectado.

## Aislamiento y procedencia

`esquema.json` contiene exclusivamente `SHOW CREATE TABLE`, `SHOW COLUMNS` y relaciones de `information_schema.KEY_COLUMN_USAGE` del entorno de desarrollo, obtenidos el 24 de noviembre de 2026. No contiene filas de negocio. Se retiraron los contadores `AUTO_INCREMENT` de los DDL y se registró el SHA-256 de cada definición.

`fixture.php` construye las tablas en orden de dependencias con las claves foráneas activas. Inserta únicamente datos ficticios mediante SQL; los catálogos utilizan códigos públicos de SAT. Los CSD y UUID son referencias ficticias sin validez: no se crea ni abre un certificado. No se usa PAC, timbrado, correo, PDF ni configuración privada. No se sustituye ninguna clase del ORM.

La conexión exige la base `qa_csd_integral`, motor sin red y la identidad del laboratorio. Se usa `SQL_MODE=''`, igual que la conexión actual de desarrollo y la suite CSD existente. La herencia `_transacciones_fc::limpia_alta_factura()` retira `total` y `descuento` antes del INSERT, por lo que esta prueba no certifica compatibilidad del ORM completo con SQL estricto. La integración SQL del servicio se comprueba por separado con su suite estricta. La limpieza elimina las tablas únicamente en ese motor efímero. El lanzador acredita además el espacio de red aislado, montajes de aplicación de solo lectura, hashes antes/después y apagado del motor. No ejecutar el guion PHP directamente contra una instalación.

## Ejecución

Desde una copia aislada del proyecto, con `QA_CSD_ENGINE_ROOT`, `QA_CSD_DEPS`, `QA_CSD_DOCUMENTO`, `QA_CSD_PLUGINS` y `QA_CSD_DDL` configurados según `pruebas/csd_integral/infra/README.md`:

```sh
python3 pruebas/complemento_orm_real/ejecutar.py
```

Solo `PASS_COMPLEMENTO_ORM_REAL_BD` y el informe del lanzador con código cero acreditan esta prueba. El resultado se guarda en `pruebas/resultados/complemento_orm_real_integracion.json`; la evidencia completa queda en `.git/qa/complemento-orm-real/ejecuciones/`.

Los casos usan MXN, facturas sin impuestos y una factura con traslado de IVA de 16%. No acreditan tipos de cambio cruzados, retenciones ni flujos fiscales reales; esas capas tienen que probarse por separado.
