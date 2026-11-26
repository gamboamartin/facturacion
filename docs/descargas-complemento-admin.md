# Descarga conjunta de complementos de pago
El botón compartido solicita la acción `descargar_por_separado`. Facturas y complementos reutilizan el mismo método protegido del controlador base, que genera dos enlaces para la sección y registro actuales. La vista del complemento descarga PDF/XML por las acciones individuales existentes; no altera la emisión, archivo XML ni lógica fiscal.

## Instalación de catálogo
Antes del despliegue se verifica respaldo recuperable del código y BD, y la existencia del grupo PA (Administrador Sistema), el usuario admin y las acciones equivalentes seguras de factura. La utilidad CLI `tools/migrations/descargas_complemento_admin.php --database=NOMBRE` imprime el plan; usar `--apply --journal=/directorio-privado/nuevo.json` exclusivamente después de autorizar ese entorno. No se ejecuta automáticamente desde web ni Composer. Crea solo las acciones faltantes del complemento y sus permisos PA; no modifica acciones existentes, usuarios ni otros grupos. Una revocación o duplicidad se considera conflicto, no se sobreescribe.

El diario contiene los identificadores de las filas nuevas antes de confirmar la transacción. En reversión, verificar que todas esas filas siguen intactas y no tienen referencias nuevas; retirar exclusivamente permisos y acciones nuevos en ese orden si se autoriza. Conservar respaldo SQL y fuente anterior, no restaurar la BD de producción completa para revertir una acción de catálogo.

## Verificación
La suite registrada `pruebas/descargas_complemento/ejecutar.py` usa únicamente un esquema efímero, ficticio, vacío y sin red; comprueba plan, transacción, grupo PA, idempotencia, revocaciones y render de los dos enlaces. No genera CFDI ni ejecuta pagos reales. Revisar también frontend y la batería completa vigente.
