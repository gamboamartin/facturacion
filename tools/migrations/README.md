# Inventario offline de migraciones

Requiere Python 3.9 o posterior, sólo biblioteca estándar. No carga PHP, configuración ni credenciales. No conecta a una base de datos, no aplica SQL y no modifica archivos.

```sh
python3 tools/migrations/inventory.py validate
python3 tools/migrations/inventory.py status
python3 -B -m unittest discover -s tools/migrations -p 'test_*.py'
```

`validate` y `status` validan el mismo manifest y presentan el estado completo. Código 0 significa únicamente manifest y hashes válidos; **no significa base preparada ni migraciones aplicadas**. Errores de estructura, acceso o integridad devuelven 1; argumentos incorrectos, 2. `database_status=not_inspected` y `deployment_readiness=not_assessed` permanecen explícitos incluso con un inventario vacío.

## Estado inicial

No hay SQL aprobado en este inventario. Las cuatro tablas figuran como requisitos `pending_definition`, no como migraciones ejecutables. Sus modelos demuestran usos, pero no determinan tipos, índices, FK, nullabilidad ni defaults. No se genera DDL a partir de ellos. Las referencias de fuentes son informativas y deben revisarse si cambia el código.

## Registrar una definición futura

Una vez obtenido y revisado un DDL de fuente autorizada, añadir manualmente un archivo `sql/<id>.sql` y una entrada con exactamente `id`, `path`, `sha256`. El ID empieza con letra minúscula y contiene únicamente letras minúsculas, dígitos o guion bajo, hasta 80 caracteres. No modificar artefactos históricos para acomodar cambios: usar una nueva definición/ID y revisar el diff. El historial Git conserva las revisiones; esta herramienta no demuestra inmutabilidad frente a alguien que edite simultáneamente SQL y manifest.

El validador comprueba archivo regular, ruta acotada, ausencia de symlinks en el archivo/directorio SQL, hash exacto y límites de tamaño (manifest 256 KiB, SQL 1 MiB). No analiza ni garantiza seguridad, idempotencia, orden de dependencias o reversibilidad del SQL. No imprime su contenido. Se presupone un checkout local confiable sin modificaciones concurrentes; no es una frontera contra un atacante con acceso al filesystem.

## Aplicación y recuperación

No existe comando `apply`, conexión, ledger ni estado `applied`. La aplicación futura necesita diseño separado: identidad inequívoca del destino, autorización, respaldo con restauración probada, revisión de dependencias/compatibilidad, estrategia de concurrencia y resultados verificables. No asumir rollback transaccional de DDL MySQL. No crear usuarios, permisos, configuración o datos como efecto implícito de este inventario.

Este directorio es independiente del arranque de la aplicación y de Composer. Los tests usan exclusivamente directorios temporales y artefactos ficticios; nunca bases reales.

Las fichas separadas en `requirements/` distinguen columnas observadas en código y definición SQL desconocida para cada tabla. Su estado bloqueado no cambia porque un archivo SQL cualquiera esté presente: el manifest no tiene un mecanismo para aprobar esas fichas ni inferir cobertura.

Un futuro aplicador tendría que implementar y probar un bloqueo exclusivo de migración, detección de cambios locales/drift, registro durable de inicio/fallo/completado, recuperación de estado interrumpido y tratamiento explícito de commits implícitos/autocommit de DDL MySQL/MariaDB. **Nada de ello está implementado aquí**. Hasta entonces, no usar el código de salida 0 para automatizar un despliegue o autorizar SQL.
