# Catálogo de precios de Partida: candidato local

Base main cbda37c6fb9d7b4bbe36ab2e6ddf9d5ce98a2bb3, árbol 90162a78e5f0aef10581e8167af9c60e97bca72c. Esta copia agrega el catálogo al candidato de lista de Partida. NO desplegada, sin commit/push.

## Cambios de producto de este bloque
- `orm/com_producto.php`: nueva API `precios_para_alta`, específica del catálogo activo ya leído, sin persistencia ni caché compartida. No reemplaza `precio(id)`.
- `controllers/_base_system_fc.php`: usa API solo para clases exactas `controlador_fc_factura` y `controlador_fc_partida`. NC, CP, clases personalizadas y otros contextos conservan el bucle anterior. No se elimina el render duplicado alta/inputs en este bloque.

## Contrato
Precedencia cliente > tipo > base; mismos redondeos PHP, overrides inactivos conservados, configuración fiscal por cliente, índices/orden y otros campos intactos. Producto inactivo queda fuera por el catálogo de origen. Catálogo vacío no valida cliente. Precios no numéricos se rechazan como original, nunca se convierten silenciosamente en cero.

Duplicados se rechazan usando las filas ya observadas, sin reconsultar un estado distinto ni permitir retorno float en API de array. Mantiene mensaje causal y datos del error. Errores SQL detienen inmediatamente, conservan causa, estructura y mensaje público, sin ocultar bandera/reintentar. Traza y capas internas de COUNT pueden diferir honestamente porque ya no hay COUNT por producto.

No hay snapshot concurrente garantizado, igual que en el código previo. Cada SELECT ve su propio resultado. Las unitarias simulan cambio después de leer filas para comprobar que no se reconsulta ni mezcla ese conjunto leído; no son certificación de concurrencia de servidor. No se agregan transacciones/bloqueos ni se modifican importes persistidos.

## Pruebas registradas
`python3 pruebas/regresion/ejecutar_phpunit.py`

87 pruebas, 267 aserciones en última batería completa: 57 anteriores byte a byte intactas, 9 de lista Partida y 21 de catálogo/alcance. Dobles de ORM en unitarias, código de aplicación real. Incluyen precedencia, redondeo, fiscal, inactivos, filtros, vacío, errores, clientes alternados, cantidad de consultas independiente del tamaño, duplicados, precios corruptos y 7 combinaciones de alcance. Fallo sintáctico inicial de la prueba de alcance conservado en `pruebas/resultados/fallo-sintaxis-alcance.json`; se corrigió solo escape del namespace, no expectativa.

## Integración sintética separada
- `/home/em3/factura-catalogo-modelo-real-20261116/final-modelo.json`: método exacto y ORM real, equivalencia de registros completos, cinco cambios de cliente, duplicados fuera de activos, errores de duplicado, vacío y guardia con clases reales. Guardia invocada por Reflection; NO ejecución completa del formulario.
- `/home/em3/modelo-real-fallo-*/final-modelo.json`: fallos PDO inyectados antes de SELECT; no fallo físico de servidor. Verifica retorno inmediato y causa sin reintento.
- `/home/em3/factura-catalogo-invalidos-20261116/invalidos1.json`: precios base/tipo/cliente corruptos, excepción TypeError igual al original.

MariaDB 10.6.11 temporal, sin red, tablas/datos ficticios, cuenta SELECT-only. Supervisores comprueban datos intactos y apagado. Wrapper externo tiene limitación conocida de cuatro namespaces ajenos ilegibles y exit2; no presentar esto como suite global exit0. Conservar todos los resultados fallidos y anteriores.

## Pendiente antes de despliegue
Formulario completo, navegación desde Factura y revisión visual en laboratorio designado. Este trabajo no resuelve por sí solo constructor/alta costosos ni ASC del framework. Revisar compatibilidad global, respaldo recuperable y recuperación antes de cualquier despliegue. Martín revisa código y autoriza commit/push; ejecutar batería vigente antes de commit. Ninguna transacción real en pruebas.
