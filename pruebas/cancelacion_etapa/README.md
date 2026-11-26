# Etapas fiscales y reenvío de cancelaciones

Ejecutar desde una copia aislada:

```sh
QA_CANCELACION_SECRET_FILE=/ruta/secret-qa.json python3 pruebas/cancelacion_etapa/ejecutar.py
```

El archivo privado contiene `dsn`, `user` y `password`. Se exige una base exclusiva vacía cuyo nombre comience con `qa_`. La suite crea y elimina sus tablas; nunca se dirige a una base de aplicación.

Ejecuta los métodos reales `_etapa::alta_bd` y `_transacciones_fc::cancela_bd`, con SQL InnoDB, índices únicos, claves foráneas, transacciones y savepoints reales. Prueba Factura, Nota de crédito y Complemento de pago; incluye reintento de cancelación, solicitud previa, snapshot externo anterior, rollback y dos procesos sincronizados intentando cancelar el mismo documento. El contador del PAC ficticio usa un archivo temporal con bloqueo, fuera de la transacción SQL, para que un rollback no oculte envíos duplicados.

El padre ORM, la consulta del proceso, la actualización de datos fiscales y el adaptador PAC son dobles locales mínimos. La prueba no certifica la herencia completa de paquetes, la respuesta fiscal del proveedor, cancelaciones SAT reales, permisos ni navegador. No carga configuración privada ni certificados, y no realiza conexiones de red al PAC. Nómina tiene un flujo diferente y queda fuera de estos modelos.

Los códigos explícitos se conservan y sus duplicados continúan rechazándose. Las descripciones siguen siendo legibles; el código automático identifica cada evento, incluso con folio/fecha repetidos. Un registro local previo bloquea el reenvío con un error de revisión; no acredita cancelación definitiva ante SAT.
