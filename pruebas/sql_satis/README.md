# Contrato e integración de gamboa.martin/sql

Desde una copia QA con las dependencias instaladas desde su lock:

```sh
python3 -B pruebas/sql_satis/ejecutar.py
```

El runner usa el `vendor/` de esa copia. Para probar otro candidato:

```sh
QA_SQL_VENDOR=/ruta/qa/vendor python3 -B pruebas/sql_satis/ejecutar.py
```

Los 21 casos verifican el contrato de los tres métodos de `gamboamartin\src\sql` —IN, coherencia y normalización de espacios— y su consumo real desde `gamboamartin\where\where::genera_in()` y `base\orm\where::in_sql()`. Incluyen valores cero, alias, cadenas con comillas y barras, errores anidados, entradas incompletas y conservación del estado de error.

Se usa Composer ClassLoader real con el mapa PSR-4 generado del candidato. Solo se habilitan los prefijos necesarios y ocho clases expresamente permitidas; cada archivo resuelto debe coincidir con su paquete. SQL y validaciones comparten el namespace `gamboamartin\src`; where tiene su namespace propio. También se verifica que SQL del paquete y SQL de administrador sigan siendo clases distintas.

No se carga `vendor/autoload.php`, ningún namespace de configuración ni la base `gamboamartin\test\test` que abre conexiones en su constructor. Las fuentes son reales; no hay dobles de SQL, where, administrador, validación o errores. La prueba usa memoria del proceso para errores y no inicia sesiones PHP ni abre bases de datos.

PHP y Python exigen identidad, orden y cantidad exacta de casos contra `registro.json`. Una terminación anticipada de PHP, incluso con código cero, no acredita la suite. La salida incluye los SHA256 de las ocho fuentes y la versión PHP.

## Límites comprobados del paquete actual

Durante la revisión se reprodujeron, sin ejecutar SQL, estos comportamientos preexistentes en v1.4.0:

- `in(' ', ' ')` devuelve `" IN ( )"`: la validación recorta espacios, pero la construcción conserva los argumentos originales.
- `in('nombre', "'A  B'")` devuelve `"nombre IN ('A B')"`: la normalización también altera espacios repetidos dentro de un literal.

Estos resultados no se presentan como funcionalidad correcta ni se corrigieron al migrar el artefacto. La suite cubre entradas válidas sin esos casos y rechazos de coherencia; no certifica sanitización SQL de datos arbitrarios. El formato de where/administrador conserva el espacio existente antes de cada coma.

No se ejecutan consultas, DDL, DML ni las pruebas históricas cuyo constructor abre PDO. No se acredita conexión MySQL, semántica de escapes bajo distintos sql_mode ni autoload completo/optimizado de la aplicación. La identidad del artefacto respecto al tag, el acceso Satis y la batería completa del proyecto se verifican por separado.
