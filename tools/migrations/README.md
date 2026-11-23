# Inventario offline de migraciones

Requiere Python 3.9 o posterior, sólo biblioteca estándar. No carga PHP, configuración ni credenciales. No conecta a una base de datos, no aplica SQL y no modifica archivos.

```sh
python3 tools/migrations/inventory.py validate
python3 tools/migrations/inventory.py status
python3 -B -m unittest discover -s tools/migrations -p 'test_*.py'
```

`validate` y `status` validan el mismo manifest y presentan el estado completo. Código 0 significa únicamente manifest y hashes válidos; **no significa base preparada ni migraciones aplicadas**. Errores de estructura, acceso o integridad devuelven 1; argumentos incorrectos, 2. `database_status=not_inspected` y `deployment_readiness=not_assessed` permanecen explícitos incluso con un inventario vacío.

## Estado inicial histórico

No hay SQL aprobado en este inventario. Las cuatro tablas figuran como requisitos `pending_definition`, no como migraciones ejecutables. Sus modelos demuestran usos, pero no determinan tipos, índices, FK, nullabilidad ni defaults. No se genera DDL a partir de ellos. Las referencias de fuentes son informativas y deben revisarse si cambia el código.

## Registrar una definición futura

Una vez obtenido y revisado un DDL de fuente autorizada, añadir manualmente un archivo `sql/<id>.sql` y una entrada con exactamente `id`, `path`, `sha256`. El ID empieza con letra minúscula y contiene únicamente letras minúsculas, dígitos o guion bajo, hasta 80 caracteres. No modificar artefactos históricos para acomodar cambios: usar una nueva definición/ID y revisar el diff. El historial Git conserva las revisiones; esta herramienta no demuestra inmutabilidad frente a alguien que edite simultáneamente SQL y manifest.

El validador comprueba archivo regular, ruta acotada, ausencia de symlinks en el archivo/directorio SQL, hash exacto y límites de tamaño (manifest 256 KiB, SQL 1 MiB). No analiza ni garantiza seguridad, idempotencia, orden de dependencias o reversibilidad del SQL. No imprime su contenido. Se presupone un checkout local confiable sin modificaciones concurrentes; no es una frontera contra un atacante con acceso al filesystem.

## Aplicación y recuperación

El inventario `inventory.py` no tiene comando `apply`, conexión, ledger ni estado `applied`. La aplicación futura necesita diseño separado: identidad inequívoca del destino, autorización, respaldo con restauración probada, revisión de dependencias/compatibilidad, estrategia de concurrencia y resultados verificables. No asumir rollback transaccional de DDL MySQL. No crear usuarios, permisos, configuración o datos como efecto implícito de este inventario.

Este directorio es independiente del arranque de la aplicación y de Composer. Los tests usan exclusivamente directorios temporales y artefactos ficticios; nunca bases reales.

Las fichas separadas en `requirements/` distinguen columnas observadas en código y definición SQL desconocida para cada tabla. Su estado bloqueado no cambia porque un archivo SQL cualquiera esté presente: el manifest no tiene un mecanismo para aprobar esas fichas ni inferir cobertura.

Un futuro aplicador tendría que implementar y probar un bloqueo exclusivo de migración, detección de cambios locales/drift, registro durable de inicio/fallo/completado, recuperación de estado interrumpido y tratamiento explícito de commits implícitos/autocommit de DDL MySQL/MariaDB. El inventario no implementa esas operaciones. Hasta entonces, no usar el código de salida 0 para automatizar un despliegue o autorizar SQL.

## Aplicador separado de diseño nuevo

`applier/` contiene un CLI explícito y un paquete nuevo de cuatro tablas, probado aisladamente. No es recuperación de DDL histórico ni marca estas tablas como aplicadas. El manifest offline y sus fichas se mantienen intactos. Leer [applier/README.md](applier/README.md) y sus contratos antes de considerar cualquier ejecución; el comportamiento predeterminado es plan offline.

## Campo de selección de logo principal

`sql/org_logo_es_principal.sql` registra el DDL focal requerido por el controlador y el servicio de logos. Añade `VARCHAR(8) NOT NULL DEFAULT 'inactivo'`; no escoge una imagen ni contiene datos de empresa. El inventario verifica su hash, pero no consulta BD ni ejecuta SQL. Las cuatro definiciones pendientes anteriores conservan su estado.

Antes de aplicar, un operador debe comprobar `SHOW COLUMNS FROM org_logo`: ejecutar solo si la tabla existe y falta `es_principal`. Si ya existe, contrastar tipo y default y no repetir el ALTER. Respaldar estructura y datos y evaluar bloqueo de tabla antes de usarlo en otro entorno. El arranque y las vistas nunca crean columnas. No usar este DDL como autorización para cambiar servidores.

En esta máquina el campo se añadió durante la preparación local del 19 de noviembre de 2026, con tabla sin logos y respaldo de estructura en `/tmp/facturacion-org-logo-esquema-20261119181047.sql`. Esta entrada hace reproducible aquella modificación; no demuestra su aplicación en otros entornos. No hay selección automática ni una imagen de reemplazo.

## Información global de factura

`sql/fc_factura_informacion_global.sql` incorpora tres códigos opcionales de periodicidad, meses y año. No rellena periodos ni modifica comprobantes existentes. Antes de aplicarlo, respaldar y comprobar ausencia de las tres columnas; si ya existen, verificar su definición. El arranque no ejecuta DDL. El contrato de captura, las representaciones vacías compatibles con el ORM y la validación están descritos en [información global](../../docs/informacion-global.md). El inventario acredita el archivo y su hash, no su aplicación en una base concreta.

## Timbrado masivo de facturas

`sql/fc_timbrado_masivo.sql` define el lote, sus resultados y el control de intentos compartido con el timbrado individual/API. La acción permite hasta 100 facturas y procesa una por petición. El navegador debe permanecer abierto; al volver a la dirección del lote se pueden consultar sus resultados y reanudar solo pendientes. Una selección de todas contiene los IDs revisados; si cambia el conjunto del filtro, se exige revisarlo de nuevo.

Las tres tablas requieren InnoDB, las claves únicas y la relación lote/elementos. El validador contrasta columnas, tipos, defaults, índices y relación antes de habilitar la acción. El instalador no transforma definiciones distintas ni vuelve a conceder permisos de una acción ya existente. Inicialmente solo copia grupos con permisos activos de `lista` y `timbra_xml`; cada petición comprueba nuevamente esos permisos y `timbrado_masivo` contra BD, además del propietario y CSRF.

El marcador heredado `SIN UUID`, predeterminado en `fc_factura.folio_fiscal`, representa ausencia de folio fiscal. Se reconoce al leer sin reescribir la factura. Un sello existente o cualquier otra evidencia fiscal siempre bloquean otro envío; los estados inciertos no se ofrecen como pendientes seleccionables.

Preparación explícita **solo en desarrollo local**, desde la raíz del proyecto:

```sh
php tools/migrations/timbrado_masivo.php --database=facturacion
php tools/migrations/timbrado_masivo.php --database=facturacion --development --apply
```

El primer comando es un plan de lectura. El segundo crea las tablas ausentes y registra la nueva acción; no modifica facturas, certificados, folios ni XML y no contacta al PAC. La migración debe estar aplicada antes de usar esta versión para timbrar individualmente o por API, porque comparten la protección de intentos. Un despliegue requiere respaldo y aplicación explícita del esquema/catálogo: este instalador no autoriza cambios de producción.

`fc_timbrado_intento.xml_respuesta` conserva el XML aceptado antes de las escrituras posteriores. No se devuelve en JSON ni se publica en archivos web. Los fallos antes del envío son corregibles; una aceptación incompleta o respuesta incierta queda para revisión y bloquea nuevos envíos de esa factura. Solo los rechazos estructurados inequívocos actualmente reconocidos (`CFDI40130`, `CFDI40145`) permiten un nuevo intento explícito después de corregir la factura. Nunca liberar un intento incierto borrando filas o cambiando su estado: primero debe verificarse el resultado con el PAC y recuperar, si existe, el comprobante original.

Las pruebas fiscales son simuladas. Las suites de integración usan una BD efímera sin red y comprueban exclusión real entre conexiones, recuperación, selección congelada y esquema; el navegador prueba progreso, confirmación y resultados con respuestas interceptadas. No acreditan un timbrado real ni una conciliación automática con el proveedor.
