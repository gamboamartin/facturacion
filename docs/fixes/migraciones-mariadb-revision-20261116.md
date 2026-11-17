# Migraciones explícitas: revisión MariaDB (16 de noviembre de 2026)

## Alcance
Diseño nuevo de cuatro tablas, no DDL histórico recuperado: cc_tipo_medidor, cc_medidor, base_movil_sesion y base_movil_push_token. Incluye corrección de alta de medidor y CLI explícito independiente del arranque de la aplicación. Publicar este código no ejecuta migraciones, cambia configuración ni habilita proyectos móviles.

Destino de esta revisión: facturacion.ivitec.mx, base ivitec, MariaDB 10.6.11. No confundir con facturacion3.konsulta.ivitec.mx ni trasladar resultados MySQL 8 a este destino.

## Protección y contrato
Solo CREATE de las cuatro tablas aprobadas; sin ALTER/DROP/DELETE/seed, usuarios o ACL. Identidad completa y fingerprint del padre, paquete con hashes, respaldo verificable, locks y journal privado. DDL autocommit: cualquier incertidumbre detiene; no hay rollback ficticio ni adopción automática de tablas existentes.

La conexión dedicada conserva los modos SQL iniciales y agrega STRICT_ALL_TABLES cuando ya existe STRICT_TRANS_TABLES. Rechaza sesiones inicialmente no estrictas. SET SESSION no altera GLOBAL ni conexiones de la aplicación. check configura su sesión, pero no modifica filas/esquema ni crea ledger.

## Evidencia
9 pruebas offline (incluyen siete casos de modos), 15 pruebas de inventario. Ensayo aislado MariaDB 10.6.11 con datos ficticios: CLI apply cuatro tablas, segundo noop y ledger intacto; modo global y otra conexión intactos. Motor/hijo/launcher finalizaron 0. Wrapper 2 por cuatro procesos del mismo UID cuya namespace no pudo leer: no se presenta como verificación integral de limpieza del host.

Ensayo separado del respaldo real, dentro del servidor correcto, en instancia temporal sin red y con datadir aislado: importación 0, 275 tablas exactas, 275 CHECK TABLE OK. Motor temporal detenido y almacenamiento temporal eliminado; respaldo original íntegro y servicios activos. No prueba restauración de toda la aplicación ni igualdad con filas actuales. Evidencia detallada, target propuesto y manifiesto compatible permanecen privados fuera del repositorio. No se incluyen credenciales, datos reales, targets ni journal.

Configuración efectiva comprobada por resolución Composer/Reflection y conexión de metadatos: config/database.php, no confs/database. Coincide byte por byte con el archivo del respaldo; base/host/motor coinciden con su manifiesto. Padre adm_usuario actual compatible y DDL coincide con respaldo sin comparar contador AUTO_INCREMENT. Las cuatro tablas siguen ausentes al preflight; no se aplicaron migraciones.

## Publicación y despliegue son pasos distintos
La instrucción es documentar y publicar primero. Antes de aplicar: revisar identidad/fingerprint/manifest privados y hashes del paquete, revalidar preflight y coordinar ventana sin mantenimiento impuesto. Un respaldo antiguo no prueba ausencia de escrituras posteriores. No ejecutar pull ni aplicar SQL automáticamente al publicar.

Las rutas tools/migrations y tests están bloqueadas por reglas específicas del vhost revisado. Otros destinos deben protegerlas o instalar herramientas fuera del webroot; el CLI no constituye una barrera HTTP. PHP-FPM compartido no fue recargado.

La eliminación de sesiones por el nuevo código fue autorizada específicamente: hasta 200 sesiones vencidas hace más de 90 días, aproximadamente 1/100 logins; límite por ejecución, no global. La FKpush→sesión usa RESTRICT y puede conservar sesiones referenciadas. No autoriza eliminar tokens, usuarios ni otros datos.

base_movil_proyecto sigue pendiente de decisión y no deshabilita login/logout/push. No seleccionar demo/base ni aguaban automáticamente. ACL de notas de crédito y pruebas funcionales siguen separadas; no se ejecutaron pruebas de negocio en producción.
