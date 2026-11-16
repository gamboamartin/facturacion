# Pruebas nuevas de nota de crédito

Fecha: 16 de noviembre de 2026.

## Ejecución reproducible

Desde la raíz del repositorio, instalar herramientas con `composer install --working-dir=pruebas/herramientas --no-plugins --no-scripts` y ejecutar `python3 pruebas/regresion/ejecutar_phpunit.py`. Usa PHPUnit 10.5.66, clases TestCase y procesos separados. El registro central contiene nombres exactos y conteos, y el resultado no cero bloquea commit. No cargar configuración ni autoload de producción. Los scripts iniciales de caracterización no son parte del conjunto activo.

## Registro creciente inicial

- NC-M01: retirar relación opcional cuando no existe la columna en la cabecera.
- NC-M02: conservar relación opcional cuando existe la columna.
- NC-M03: nombres de entidad y expresiones de descuento/UUID.
- NC-M04: alta con descripción e importe ficticios conserva valores, activa saldo e inicializa correo/etapa NC.
- NC-M05: modificación conserva registro, identificador y opciones de validación.
- NC-C01: alta del controlador propaga modelo, resultado y tres combinaciones de header/ws.

Resultado inicial: 8 casos, 8 éxitos, 0 fallos. Los archivos NC probados coinciden por SHA-256 con runtime/source del entorno aislado partida-datatable-orm-20261116.

## Aislamiento y límites

Se carga la implementación real de ambas clases NC. Se sustituyen expresamente las clases padre y colaboradores por dobles locales. PDO no abre conexión y prohíbe query, prepare y exec. No se usa autoload ni configuración productiva. Se omite el constructor del controlador mediante reflexión: estas pruebas certifican exclusivamente la lógica propia NC y la delegación, no la implementación heredada, renderizado, SQL, persistencia, constructor completo, timbrado, cancelaciones ni rendimiento.

No equivale a ocho flujos funcionales completos. Complementar posteriormente con pruebas de integración aisladas de herencia, constructor y vistas; no ampliar permisos de esta batería ni usar datos reales. No se cambió código de aplicación ni hubo commit o push. El retiro recuperable de las pruebas históricas en la copia nueva está documentado en el protocolo general.

## Inventario heredado

Consultar docs/regresion/inventario-anterior.json y PROTOCOLO.md. Las pruebas antiguas fueron respaldadas y retiradas del conjunto activo de la copia nueva, sin borrado definitivo ni cambios productivos.
