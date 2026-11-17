# cc_medidor — BLOQUEADO: definición pendiente

No es una migración. Fuente `orm/cc_medidor.php`: constructor declara tabla cc_medidor y relación ORM cc_tipo_medidor. alta_bd escribe explícitamente tipo_medidor_id, descripcion y codigo; toma el valor del input cc_tipo_medidor_id y devuelve lastInsertId.

Discrepancia que debe resolverse con DDL auténtico: columna escrita tipo_medidor_id frente a convención de relación cc_tipo_medidor. No renombrar ni añadir ambas por inferencia. Tipos, PK/autoincrement, FK, índices, null/default, campos comunes, engine/collation desconocidos. Ninguna definición ejecutable hasta revisar esquema y contrato de los consumidores.

## Consumidores comprobados

- `controllers/controlador_cc_medidor.php:39-46` declara listado/filtros de `cc_medidor.id`, `cc_medidor.descripcion` y `cc_tipo_medidor.descripcion`; aliases de resultado `cc_medidor_id`, `cc_medidor_descripcion` y `cc_tipo_medidor_descripcion`.
- Líneas59-62 generan el selector requerido con nombre `cc_tipo_medidor_id`; líneas76-82 delegan Modifica al padre. No resuelven por sí mismas la discrepancia con el INSERT `tipo_medidor_id` del modelo.
- `views/cc_medidor/modifica.php:15-16` contiene un hidden con id `cc_tipo_medidor_id`, sin atributo `name`, situado antes de abrir el formulario. Por sí solo no se envía como control de ese formulario. Es evidencia estática limitada: no acredita un fallo integral ni descarta lógica adicional del controlador/JavaScript. La descripción sí se renderiza dentro del formulario en línea19.

Estos campos y nombres están respaldados por código; su tipo SQL, restricciones físicas y persistencia correcta todavía no están demostrados.
