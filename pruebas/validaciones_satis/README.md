# Contrato e integración de gamboa.martin/validaciones

Desde una copia QA con dependencias instaladas desde su lock:

```sh
python3 -B pruebas/validaciones_satis/ejecutar.py
```

Por defecto se usa el `vendor/` de esa copia. Para seleccionar un candidato:

```sh
QA_VALIDACIONES_VENDOR=/ruta/qa/vendor python3 -B pruebas/validaciones_satis/ejecutar.py
```

También se acepta `QA_VENDOR` cuando no se define `QA_VALIDACIONES_VENDOR`; la variable específica tiene prioridad.

Los 26 casos ejercitan el método real `gamboamartin\src\validaciones::valida_data_filtro_especial()`, su herencia de `gamboamartin\validacion\validacion`, errores y entradas válidas (incluidos cero y texto Unicode). Verifican que los datos no muten, que arrays y operadores ausentes/null se rechacen, y que las causas sobrevivan a las envolturas de error.

La integración ejecuta `gamboamartin\where\where::filtro_especial_sql()` real: valores, alias de columnas, varios filtros AND y rechazos sin devolver una cadena SQL parcial. Las trazas de errores comprueban que este flujo use el paquete plural. Se prueba también `base\orm\where::filtro_especial_sql()`, que actualmente utiliza su propia clase `gamboamartin\administrador\modelado\validaciones`. Ese último recorrido acredita compatibilidad; no se atribuye como uso directo del paquete plural.

Se utiliza Composer ClassLoader real con el mapa PSR-4 generado del candidato, solo prefijos necesarios y ocho clases permitidas. Se carga validaciones plural antes que SQL y se verifica la fuente exacta de cada clase del namespace compartido. No se carga `vendor/autoload.php`, configuración privada ni la clase base de pruebas que abre PDO/mysqli. No hay dobles de los consumidores o validadores.

PHP y Python exigen coincidencia de nombres, orden y cantidad de casos con `registro.json`. Una salida anticipada de PHP con código cero se rechaza. La salida informa assertions, versión PHP y SHA256 de las ocho fuentes.

## Alcance y límites

El parámetro `$filtro` se recibe por valor. Si falta `valor`, el método plural lo admite mediante un valor predeterminado local, pero no modifica el argumento del llamador; esto contradice el ejemplo del comentario que promete una mutación externa. Los consumidores where/administrador exigen el valor explícito y lo rechazan si está ausente. Ambas capas se verifican sin alterar el contrato existente.

Este validador comprueba estructura limitada: no constituye una lista de operadores permitidos ni una validación completa de tipos escalares. Las pruebas no acreditan sanitización o seguridad de SQL arbitrario. No se ejecutan consultas, DDL, DML, BD, PAC ni sesiones PHP. Tampoco se ejecutan las pruebas históricas cuyo constructor abre conexiones.

La identidad del artefacto respecto al tag, el acceso/autenticación Satis, el autoload completo/optimizado y la batería completa del proyecto se verifican por separado.
