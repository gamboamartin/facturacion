# Descarga individual de XML y PDF

El usuario solicitó recuperar la descarga separada desde cada fila de Factura, Nota de crédito y Complemento de pago, así como asignarla a admin y al grupo 3 principal. Esta suite comprueba el backend; el navegador secuencial y sus vistas tienen pruebas separadas.

```bash
QA_DESCARGAS_SECRET_FILE=/ruta/privada/qa-metadata.json python3 pruebas/descargas_documentos/ejecutar.py
```

La credencial contiene `dsn`, `user` y `password` de un esquema MySQL local **vacío**, con prefijo `qa_descargas_` o `qa_productos_permisos_`. También se admite el recurso existente `QA_PRODUCTOS_SECRET_FILE`. La suite crea y elimina únicamente sus cinco tablas de catálogo ficticias; no necesita configuración, certificados ni documentos de negocio. Falla si la base no está vacía. No ejecutar simultáneamente con otra suite que use ese mismo esquema.

Los 52 casos cubren los tres controladores reales con dobles de ACL, renderizado y persistencia; combinaciones de permisos por formato; ausencia de persistencia después de enviar un PDF; conservación del guardado para adjuntos; y una respuesta binaria sin HTML posterior. Incluyen 24 casos con MySQL real y CLI: plan, alcance de perfiles, identidades, idempotencia, rechazo de revocaciones, diario durable, fallos antes/después del commit y reversión propia sin retirar accesos anteriores. La configuración histórica de `get_data` de NC/CP se conserva; los endpoints de descarga permanecen protegidos.

## Migración de catálogo y permisos

Se ejecuta explícitamente desde CLI, fuera de las peticiones web. Agrega solamente acciones/permisos faltantes para `lista`, `get_data`, `descargar_por_separado`, `descarga_xml` y `genera_pdf` de esas tres secciones, a los grupos del usuario admin y del grupo 3 con código confirmado. No concede timbrado, altas, modificación ni eliminación de documentos.

1. Confirmar dominio, base, grupo 3 y admin en cada instalación. Respaldar y suspender sus escrituras durante la aplicación.
2. Generar plan: `php tools/migrations/descargas_documentos_permisos.php --database=BASE --interface=DOMINIO --grupo-principal-codigo=Sistema > /directorio-privado/plan-permisos.json`.
3. Revisar el plan. Aplicar con los mismos argumentos y `--apply --plan=/directorio-privado/plan-permisos.json --journal=/directorio-privado/acl-journal.json`. El diario debe ser nuevo, absoluto, estar fuera de la aplicación y dentro de un directorio `0700`.
4. Verificar metadatos, permisos efectivos y rutas de los tres formatos. Los hashes del plan evitan aplicar si usuarios, grupos o catálogo cambiaron después de la revisión.
5. Para revertir, usar los mismos argumentos con `--rollback=/directorio-privado/acl-journal.json`. La reversión reconoce también un diario `PENDIENTE_CONFIRMACION`: el commit puede haberse completado aunque falle la última escritura del reporte. Sólo retira filas creadas intactas; rechaza filas modificadas o acciones nuevas que otro permiso ya use.

No comparar contadores AUTO_INCREMENT para determinar la reversión lógica: insertar y deshacer una operación puede consumir números aunque las filas queden exactamente como antes.
