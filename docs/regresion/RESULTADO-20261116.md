# Resultado inicial de regresión

Fuente de aplicación: main 14e8dbaf6e3ad70797315fdc28240228e7f1acdc. PHP 8.3.17, PHPUnit 10.5.66. Ejecutor `python3 pruebas/regresion/ejecutar_phpunit.py`.

- Factura: 10 pruebas, 44 aserciones.
- Nota de crédito: 8 pruebas, 21 aserciones.
- Complemento de pago: 12 pruebas, 12 aserciones.
- Total: 30 pruebas, 77 aserciones, cero errores, fallos u omisiones. Resultado inicial exitoso; debe repetirse después de cualquier modificación y antes de commit.

No es cobertura completa de negocio ni medición porcentual de líneas. Las unidades usan dobles y datos sintéticos; ninguna transacción fiscal ni conexión productiva. No se corrigió código aplicativo en esta etapa. Cotejo de los 881 archivos originales: cero modificaciones de contenido; retirados únicamente los 72 archivos de tests/ y caché PHPUnit, con respaldo. Los cinco scripts de migraciones ajenos a esa carpeta siguen pendientes de decisión.

Registro de casos y hashes: pruebas/regresion/registro.json. Evidencia de ejecución generada: pruebas/resultados/resultado.json y phpunit.xml. Sin commit, push ni despliegue.

## Ampliación posterior: éxitos y errores

Ejecutada batería completa: **57 pruebas y 183 aserciones**, 0 errores, fallos u omisiones; registro de identidades exacto y código de salida 0.

- Factura: 18 pruebas, 84 aserciones. Añade errores de reportes, padre alta/modifica y dependencias de consulta por sucursal.
- Nota de crédito: 17 pruebas, 59 aserciones. Añade propagación de fallos alta/modifica/elimina, dependencias de eliminación, defaults y variantes de error de controlador.
- Complemento de pago: 22 pruebas, 40 aserciones. Añade IDs cero/negativos, fallos simulados de validación, estado no inicializado y repetibilidad.

Los errores se inyectan en colaboradores dobles: se verifica respuesta de las clases propias, no se certifica el funcionamiento completo de esas dependencias reales. Cotejo final nuevamente sin cambios a los 808 archivos originales conservados, 73 retirados recuperables. Respaldo completo de 78 archivos revalidado por SHA256 individual.

## Preparación de publicación autorizada

Se verificó instalación limpia desde composer.lock con red desactivada y caché Composer: 26 dependencias instaladas, incluido PHPUnit 10.5.66, sin plugins ni scripts. Se conserva respaldo recuperable; los cinco scripts de migraciones quedan fuera del nuevo ejecutor y pendientes de decisión. La autorización de commit y push cubre exclusivamente pruebas, retiro histórico y documentación; no despliegue ni cambios aplicativos. Antes de crear el commit se vuelve a ejecutar el conjunto íntegro de 57 casos en el repositorio final.
