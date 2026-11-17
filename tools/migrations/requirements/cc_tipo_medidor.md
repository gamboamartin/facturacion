# cc_tipo_medidor — BLOQUEADO: definición pendiente

No es una migración. Fuente `orm/cc_tipo_medidor.php`: hereda _modelo_parent; constructor registra tabla cc_tipo_medidor y campos_obligatorios vacío. Eso no demuestra tabla sin campos obligatorios ni defaults SQL.

Pendiente DDL auténtico y cierre de campos desde consumidores/ORM: tipos, columnas completas, PK/autoincrement, índices, FK, null/default, auditoría, engine/collation. No derivar un CREATE TABLE sólo del nombre del modelo.

## Consumidores comprobados

- `controllers/controlador_cc_tipo_medidor.php:37-42` declara listado/filtros de `cc_tipo_medidor.id` y `cc_tipo_medidor.descripcion` (aliases de resultado `cc_tipo_medidor_id` y `cc_tipo_medidor_descripcion`). Esto acredita esos nombres de campos usados por lectura, no sus tipos SQL.
- Líneas47-59 construyen acciones Alta/Modifica y delegan al controlador padre. No contienen DDL ni un listado cerrado de columnas persistidas; faltan contratos efectivos del padre y esquema para completarlo.
