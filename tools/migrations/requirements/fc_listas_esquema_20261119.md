# Esquema focal de listados FC — 2026-11-19

## Fuente y alcance

SQL derivado únicamente de metadata de FC3 obtenida por el coordinador y contrastada con la metadata local. No se copian registros, certificados, credenciales ni datos fiscales.

Evidencia de preparación (SHA256):

- `esquema-referencia.json`: `7f6f31cf206e7949d1091b7d89b96c84e749ee2be411fcee81903329a36a07da`.
- `esquema-local.json`: `cbdb806e5bc06bb7b0b59fccbcca12cbc00af4ff3a2670f80edbb284248f7629`.

Se añaden dos columnas de relación de cabecera, una tabla vacía de periodos, catorce columnas faltantes de `fc_layout_nom` y cinco de `fc_row_layout`. No modifica `fc_nota_credito`, `fc_row_nomina`, registros existentes mediante DML, columnas ya presentes, claves o relaciones existentes. No omite JOIN del modelo para ocultar incompatibilidades. Otras diferencias entre instalaciones quedan fuera de esta migración.

## Precondiciones que deben comprobarse antes de ejecutar cualquier sentencia

1. Destino explícitamente local de desarrollo, motor MySQL compatible con la metadata, charset `utf8mb3` y collation `utf8mb3_general_ci` disponibles. Verificar `SELECT VERSION()` y soporte efectivo en el ensayo desechable.
2. Respaldo recuperable de esquema y datos de las cuatro tablas alteradas; comprobar restauración antes de la aplicación local. DDL hace commit implícito: una transacción no garantiza deshacer la secuencia.
3. `fc_layout_periodo` debe estar ausente. No usar `CREATE TABLE IF NOT EXISTS`: una tabla presente requiere comparar definición y detener esta migración.
4. `fc_factura.com_tipo_producto_id` y `fc_complemento_pago.com_tipo_producto_id` deben estar ausentes.
5. Deben estar ausentes las catorce columnas `ADD COLUMN` de `fc_layout_nom` y las cinco de `fc_row_layout` definidas en el SQL. Comparar los metadatos locales con la captura de referencia; si cualquier columna ya existe, detenerse y preparar un plan nuevo, no omitir sentencias silenciosamente.
6. Las tablas alteradas deben existir y conservar su identidad y estructura inspeccionadas. El runner debe comprobar todas las precondiciones antes del primer DDL para evitar una aplicación parcial por drift ya conocido.
7. Ensayar sobre restauración desechable con el mismo modo SQL de la conexión final y datos locales respaldados. Conservar advertencias y comparar conteos, claves, definiciones y valores anteriores; las columnas numéricas nuevas NOT NULL sin DEFAULT explícito conservan exactamente esa definición remota. No convertir su valor histórico inicial en un importe validado ni inventar una regla de recálculo.

## Definiciones conservadas

- Factura: `com_tipo_producto_id bigint NOT NULL DEFAULT '-1'`.
- Complemento: `com_tipo_producto_id bigint NOT NULL DEFAULT '99999999'`.
- `fc_layout_periodo`: CREATE remoto íntegro salvo contador `AUTO_INCREMENT=97`, que es estado de datos remoto y se retira; la columna ID sí conserva `AUTO_INCREMENT`.
- Columnas de layout y filas: definiciones remotas exactas, sin añadir FK, índices o DEFAULT que la referencia no define. Columnas de texto sin charset explícito heredan `utf8mb3` del destino inspeccionado.

La tabla de periodos queda vacía. No se insertan IDs `-1` ni `99999999` en catálogos ni se asignan periodos/sucursales a datos locales. La semántica de datos históricos y flujos de altas debe validarse por separado después del esquema.

## Comprobación y recuperación

Después de aplicar en copia y, posteriormente, en local autorizado: comparar las 21 columnas nuevas con la referencia, la estructura de periodos sin contador y los conteos/valores de columnas preexistentes contra respaldo. Repetir las respuestas AJAX de lectura de los listados y exigir su contrato DataTables; un resultado vacío no sustituye un error SQL.

No ejecutar timbrado, cancelación ni recálculos para comprobar listados. Si la aplicación falla después de un DDL parcial, detenerse y restaurar las tablas desde el respaldo verificado. No borrar columnas o tabla como rollback genérico después de que puedan haber recibido datos.

Esta preparación no ejecutó SQL ni afirma que el ensayo o la aplicación hayan pasado; esos resultados los registra el coordinador con evidencia.
