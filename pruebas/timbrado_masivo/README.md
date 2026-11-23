# Integración del timbrado masivo

El ejecutor utiliza el motor MariaDB efímero y el aislamiento existente de
`pruebas/csd_integral/infra/`. Se ejecuta desde una copia aislada del repositorio,
con los recursos `QA_CSD_ENGINE_ROOT`, `QA_CSD_DEPS`, `QA_CSD_DOCUMENTO`,
`QA_CSD_PLUGINS` y `QA_CSD_DDL` configurados:

```sh
python3 pruebas/timbrado_masivo/ejecutar.py
```

La única base visible para el fixture es `qa_csd_integral`, creada en tmpfs dentro
de un namespace sin red. El host, dependencias y fuentes están montados en modo
lectura. El ejecutor acredita hashes de las clases reales, identidades del registro,
limpieza de tablas, cierre del motor y ausencia de procesos visibles restantes en
el namespace. La evidencia histórica queda en `.git/qa/timbrado-masivo/ejecuciones/`
y el resumen en `pruebas/resultados/timbrado_masivo_integracion.json`.

Los 36 casos ejercitan SQL real de `_timbrado_lote`, `_timbrado_pendientes`,
`_timbrado_guardia`, `bloqueo_uso_csd` y `EsquemaTimbradoMasivo`, con
un esquema mínimo sintético y el DDL operativo real. Cubren filtros y caracteres
literales, elegibilidad histórica, paginación, IDs congelados, selección de todas rechazada si cambia el conjunto revisado,
límite 100/101,
idempotencia, propiedad del lote, revocación del callback de permiso, repetición,
bloqueo con dos conexiones, recuperación por fase, resultados mixtos y fallos
posteriores al envío o al sello simulado. Verifican también que una transacción
ajena no se revierte, autocommit obligatorio, identidad fiscal coherente, marcador heredado SIN UUID permitido solo sin evidencia
fiscal real, exclusión
real con dos conexiones, respuesta XML durable antes del guardado local, bloqueo
tras timeout y reintento explícito solo para el rechazo conocido CFDI40130.
Se rechazan esquemas con índices únicos/FK ausentes, tipo alterado o motor MyISAM,
y se verifica de nuevo el esquema restaurado después de cada variante.
No se abre una transacción global: las
confirmaciones del servicio forman parte de la prueba; al final se eliminan las
tablas y se destruye el motor efímero.

El callback fiscal es falso: únicamente incrementa un contador y escribe un sello
sintético o un estado de intento. No carga ORM legacy, certificados ni configuración
privada, no llama endpoints ni PAC y no produce documentos fiscales. Esta suite
no acredita el controlador HTTP/CSRF, su consulta real de ACL, el flujo completo
del ORM legacy ni la recuperación con un PAC real. Esas capas requieren
sus pruebas independientes; `PASS_TIMBRADO_MASIVO_INTEGRACION` no las sustituye.
