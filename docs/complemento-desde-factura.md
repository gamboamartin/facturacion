# Complemento de pago desde la lista de facturas

La acción **Complemento de pago** de cada fila abre la preparación de un complemento vinculado a esa factura. Propone el saldo pendiente completo, que puede editarse para registrar un pago parcial.

## Uso

1. En Facturas, abre **Complemento de pago** en las acciones de la factura.
2. Revisa emisor, cliente, serie y folio, UUID, moneda, saldo y siguiente parcialidad precargados desde la base de datos.
3. Confirma un CSD activo del mismo emisor. Captura la fecha y hora reales del cobro, su forma de pago y el tipo de cambio correspondiente. Ajusta el monto cuando el pago sea parcial.
4. Confirma los datos y guarda. Se crean el complemento pendiente, su pago y el documento relacionado. El saldo de la factura se actualiza conforme a la política existente del sistema.
5. Continúa en la pantalla habitual de edición del complemento. Guardar en esta acción no ejecuta el PAC ni timbra automáticamente.

La forma de pago recibida debe elegirse expresamente. No se copia la clave 99 de la factura como forma del cobro. La cabecera del comprobante de pago y la moneda del pago son referencias distintas; el servicio utiliza los catálogos y los modelos existentes para construirlas.

## Condiciones y permisos

La factura debe estar activa, timbrada, con método PPD, UUID sellado consistente y saldo pendiente. Una cancelación registrada en el historial también impide usar esta acción. El CSD elegido debe pertenecer a la misma empresa y RFC emisor; se ofrecen certificados activos para permitir su rotación.

El usuario y su grupo deben estar activos y tener estas cuatro autorizaciones activas:

- `fc_factura / lista`
- `fc_factura / complemento_pago`
- `fc_complemento_pago / alta_bd`
- `fc_complemento_pago / modifica`

Se comprueban en el servidor y se vuelven a consultar antes de escribir. La acción no concede permiso para timbrar. Se administra por grupo en Seguridad; quitar `fc_factura / complemento_pago` conserva las autorizaciones de alta o timbrado por las vías ordinarias.

El catálogo de acciones genera el botón de fila de DataTables. La acción no aparece como una operación global de la barra superior porque requiere una factura concreta.

## Instalación en desarrollo

El registro del catálogo es explícito y no se ejecuta al abrir una vista. Primero consulta el plan:

```sh
php tools/migrations/complemento_desde_factura.php --database=facturacion
```

Con un respaldo verificado de la base local de desarrollo, aplica:

```sh
php tools/migrations/complemento_desde_factura.php --database=facturacion --development --apply
```

Solo se crean la acción y sus asignaciones iniciales para grupos que ya pueden listar facturas, crear y editar complementos. No se amplían sus permisos anteriores. Repetir la instalación conserva la acción existente y no repone autorizaciones que un administrador haya retirado.

El instalador de línea de comandos rechaza la aplicación en bases distintas de `facturacion`. Un despliegue a otros entornos requiere un operador que registre el mismo catálogo con el destino, respaldo y permisos administrativos de ese entorno; no debe forzarse el modo de desarrollo en producción.

## Integridad y límites

- Abrir el formulario no crea complementos, pagos ni documentos relacionados. La consulta del servicio usa SQL de lectura. Los constructores heredados del entorno completo pueden inicializar los tipos de cambio del día; esa conducta anterior no se modifica en esta acción.
- El POST requiere CSRF y una clave de solicitud. Los campos fiscales de autoridad se obtienen nuevamente del servidor; un formulario no puede imponer otro cliente, UUID, saldo o parcialidad mediante campos ocultos.
- La creación usa una transacción y bloqueos. Un fallo revierte los registros parciales. Reenviar la misma solicitud permite recuperar el complemento creado sin duplicarlo; reutilizarla con datos diferentes se rechaza.
- La vía compartida de alta de documentos relacionados sincroniza saldo y parcialidad bajo bloqueo, también para formularios heredados.
- Se conservan las conversiones de los modelos existentes: misma moneda y cruces con MXN. El nuevo flujo no ofrece un cruce directo entre dos divisas extranjeras distintas.
- Los permisos de acción no incorporan un nuevo sistema de aislamiento por empresa o usuario. La instalación conserva el alcance de acceso ya definido por sus grupos.

## Validación

Ejecutar desde una copia aislada, con las herramientas fijadas y los recursos del laboratorio indicados por `AGENTS.md`:

```sh
python3 pruebas/ejecutar_completa.py
```

Las pruebas nuevas se distribuyen entre contratos unitarios, SQL sintético con control de rollback y concurrencia, navegador con respuestas interceptadas y verificación de la cadena de modelos. Sus registros y resultados distinguen cada alcance; ninguna prueba de esta entrega requiere timbrar comprobantes reales.

La suite del servicio usa SQL estricto. La prueba de la cadena ORM completa reproduce el modo SQL permisivo de la instalación de desarrollo, del que ya dependen las altas heredadas. La cadena completa no queda certificada para SQL estricto: la limpieza heredada de cabecera omite algunos importes que su esquema conserva sin valor predeterminado. Esta acción no cambia el modo SQL ni la configuración del servidor; la compatibilidad de la sesión de aplicación debe comprobarse antes de desplegar a otro entorno.

Referencia de los datos del pago y del documento relacionado: [Complemento de pagos del SAT](https://wwwmat.sat.gob.mx/consultas/92764/comprobante-de-recepcion-de-pagos).
