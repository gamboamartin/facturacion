# Listas FC y nómina: corrección local del 19 de noviembre de 2026

## Causas y solución

La alerta DataTables `DataTables_Table_0 - 1` correspondía al JSON de error del backend (`error: 1`), no a una falla visual que debiera ocultarse.

1. La base local carecía de `com_tipo_producto_id` en factura y complemento. El JOIN del modelo fallaba. Nota de crédito ya respondía correctamente.
2. Nómina carecía de `fc_layout_periodo` y 19 columnas de layouts/filas. Se comparó metadata de la instancia FC3 de referencia mediante SHOW CREATE/SHOW FULL COLUMNS, sin consultar ni copiar registros remotos.
3. Búsqueda de complemento utilizaba un alias SELECT en WHERE. El filtro pasa de `fc_complemento_pago_etapa` a `fc_complemento_pago.etapa`; el alias de la columna de salida no cambia.
4. `administrador::get_data_lista()` reiniciaba offset a cero cuando el total era menor o igual al tamaño de página. Se corrigió la fuente de la dependencia y se publicó v22.44.3 con autorización expresa. No se mantuvo una modificación manual de código en vendor ni se alteró DataTables para ocultar el fallo.

## Esquema y datos

La migración focal versionada `tools/migrations/sql/fc_listas_esquema_20261119.sql` contiene cinco sentencias: 21 columnas y una tabla vacía. Sus tipos/defaults provienen de la referencia; no se inventaron FKs ni catálogos ni se importaron datos remotos. El inventario offline verifica el hash, no aplica SQL ni certifica una base.

Antes del cambio local se fijaron destino loopback/base facturacion, SHA SQL, esquema y conteos. Se mantuvieron bloqueos WRITE efectivos durante respaldo/DDL/comparación, usando conexión separada para la tabla nueva; una tercera conexión verificó bloqueo antes y después de cada paso. Se conservó un journal pendiente/aplicado/verificado y warnings.

El respaldo privado de estructura y datos se restauró en un esquema QA temporal, se comparó fila por fila y se ensayó allí el DDL completo antes de tocar la base local. En QA se desactivaron FK solo para reconstruir tablas cuyas dependencias no se copiaron: esto acredita recuperación fiel del subconjunto respaldado, no integridad referencial completa de toda la aplicación. El esquema temporal se retiró.

Resultado local: todas las columnas originales de **328 facturas y 1 complemento** coinciden exactamente con el respaldo; layouts y filas estaban vacíos y permanecen vacíos. Defaults nuevos contrastados con referencia; periodo sin registros. MySQL emitió avisos de deprecación de utf8mb3/collation y precisión FLOAT/DOUBLE, conservados en el journal; no hubo truncamientos ni cambios de datos originales.

Hay ocho columnas adicionales de factura presentes en la referencia y ausentes en esta instalación que no intervienen en esta reparación focal; no se copiaron automáticamente. Esta migración no equivale a igualar toda la base con producción ni a certificar otros flujos de negocio.

## Dependencia publicada e instalada

Paquete `gamboa.martin/administrador` **v22.44.3**, commit `9f09c92192c1c6e77774ab2c3532a8b3796e1ab4`:

- Fuente oficial: https://github.com/gamboamartin/administrador/tree/v22.44.3
- Único cambio runtime: retirar cuatro líneas que reiniciaban offset; incluye prueba focal y nota de versión.
- SHA256 de `base/orm/modelo.php`: `9e33d6c2965cc3e2a4ad37cd839970c7638b5afac43b064ca226a111623f2f25`.
- Satis incorpora el paquete sin retirar los tres paquetes anteriores ni sus archivos. La publicación cambió el enlace current de forma atómica y conservó los releases anteriores.
- El build inicial detectó nombre inválido y después autenticación GitHub fallida; se utilizó un ZIP generado desde el commit oficial publicado, con hashes verificados y constructor aislado. No se cambiaron credenciales ni ACL.
- La validación final detectó permisos0600 dentro del primer ZIP rearchivado por el constructor. Se conservó aquel archivo, se publicó otro ZIP con contenido idéntico por entrada y permisos644/755, URL/checksum/referencia de artefacto propios. Git tag y source.reference permanecen intactos. `dist.reference` identifica por SHA256 el nuevo ZIP; no se hace pasar por commit Git.
- Un ensayo Composer aislado acreditó que actualización parcial (sin --lock) detecta ese dist.reference y reinstala. HTTP autenticado verificó raíz, includes, p2 y ZIP; `composer.lock` e `installed.json` coinciden con la distribución normalizada y la fuente instalada tiene el hash esperado. Un lock anterior requiere actualización parcial para recibir ese archivo; un reinstall a ciegas no actualiza metadata.
- composer.json exige ^22.44.3 e incluye administrador en la lista de paquetes de Satis. Solo cambió la versión de esa dependencia; Composer retiró campos published-time de metadata de otros dos paquetes sin cambiar sus versiones ni código instalado.

## Métricas ejecutadas

| Comprobación | Resultado |
| --- | --- |
| HTTP antes | 3/12 PASS; las tres solicitudes NC funcionaban. |
| Tras esquema | 10/12 PASS; se detectaron búsqueda y paginación CP. |
| Tras filtro CP | 11/12 PASS; el caso de página fuera de rango siguió fallando sin relajar su expectativa. |
| HTTP final real | **12/12 PASS**: primera página, siguiente y búsqueda sin coincidencias en factura, NC, CP y fc_layout_nom. Verifica JSON, HTTP, aliases, draw, conteos y offsets sin guardar filas ni sesiones. |
| PHPUnit aplicación aislado | **178 pruebas / 807 aserciones**, EXITO_UNITARIO, registro exacto, sin fallos ni omisiones. |
| Tabla empleados nómina | 3 casos y 126 aserciones incluidos en 178; render real 22 encabezados, filas sintéticas 22 celdas, selector y targets JS. Negativas detectan celda/selector/target/warning roto. |
| Verificador HTTP | 14 pruebas sintéticas PASS; rechaza HTTP200 con error:1, HTML, aliases faltantes y contratos inválidos. |
| Frontend existente | 21 pruebas estáticas PASS. |
| Inventario migraciones | 15 pruebas PASS y hashes válidos; no significa esquema aplicado. |
| Dependencia instalada | 7 casos / 37 aserciones de paginación PASS; versión previa falla dos. Header_out 36 y regresión Partida 8 del paquete también PASS. |
| Detalles ORM reales | fc_row_layout y fc_row_nomina: consultas con JOIN y filtro imposible dentro de transacción READ ONLY, sin errores, 0 filas. |
| Navegador local | Factura muestra 328 y cambia de 1-10 a 11-20; CP muestra 1 y búsqueda imposible vacía sin alerta; NC y layouts vacíos cargan sin alerta ni NaN. |
| Instalación | Plataforma Composer PASS, source/lock/installed/Satis concordantes y permisos estándar. |

La nómina web se llama `fc_layout_nom`; `fc_row_layout` y `fc_row_nomina` son modelos de detalle, no rutas web autónomas. No se creó nómina para aparentar datos de prueba.

No se ejecutaron timbrado, cancelaciones, pagos ni generación de recibos/PDF. La batería integral CSD/MariaDB/FPM requiere su laboratorio QA_CSD y sigue fuera de esta verificación; no se declara PASS_BATERIAS_REGISTRADAS. Al cierre de aquella verificación local, el código de Facturación aún estaba sin commit/push y solo se había publicado la dependencia autorizada. Ese estado histórico no describe publicaciones posteriores; consultar el historial de Git y los informes de despliegue correspondientes.

## Evidencia y recuperación

Respaldo, journal, snapshots de metadata y reportes sanitizados se conservan localmente en `.git/qa/listas-20261119/`, con directorio 0700. No forman parte de archivos versionados. Los resultados HTTP no contienen tokens ni filas; el respaldo de datos es privado. Se preservan copias de código/Composer anteriores. Los DDL MySQL no tienen rollback transaccional: cualquier restauración requiere aislar el destino, revisar escrituras posteriores y usar el respaldo; no ejecutar DROP COLUMN/tabla como reversión automática.
