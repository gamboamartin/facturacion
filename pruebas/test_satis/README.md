# Contrato aislado de gamboa.martin/test

Ejecutar desde una copia local aislada del proyecto con las herramientas fijadas en su lock:

```sh
python3 -B pruebas/test_satis/ejecutar.py
```

El runner carga únicamente las fuentes reales `src/liberator.php` y `src/test.php` del paquete, la clase real de errores y el autoload de PHPUnit de `pruebas/herramientas/vendor/`. No carga el autoload de la aplicación.

Se pueden seleccionar las fuentes de un artefacto candidato mediante `PAQUETE_TEST`, `FUENTE_ERRORES` y `AUTOLOAD_PHPUNIT_AISLADO`. Sus valores deben ser rutas locales a recursos de prueba; no se instala ni actualiza ninguna dependencia.

Los 16 casos comprueban lectura y escritura de propiedades privadas/protegidas, invocación de métodos con argumentos, herencia, aislamiento entre objetos, errores de reflexión y tipos, preservación de excepciones y generación de registros. Se exige identidad, orden y cantidad exacta de casos contra `registro.json`, y la salida incluye los SHA256 de las fuentes ejercitadas.

La clase `test` se instancia con `ReflectionClass::newInstanceWithoutConstructor()`: sus métodos de registro se ejecutan realmente, pero sus constructores PDO/mysqli, SQL de sesión y definición de UT no se ejecutan. PHPUnit es real; no se sustituye su clase base con un doble. Las variables de errores y sesión son memoria del proceso; no se inicia sesión PHP.

Esta prueba no acredita conexiones de base de datos, el transporte/autenticación Satis ni identidad del artefacto frente al tag. Tampoco sustituye la batería completa del proyecto. Una migración de distribución debe comprobar adicionalmente que el paquete conserva sus bytes respecto de la fuente autorizada.
