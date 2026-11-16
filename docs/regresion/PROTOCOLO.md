# Regresión de Facturación desde el 16 de noviembre de 2026

## Ejecución única
Desde la raíz de una copia local aislada:

    composer install --working-dir=pruebas/herramientas --no-plugins --no-scripts
    python3 pruebas/regresion/ejecutar_phpunit.py

El segundo comando es la puerta previa a commit. Ejecuta PHPUnit, exige todos los casos registrados (57 en la ampliación actual) sin omisiones y guarda `pruebas/resultados/resultado.json` con hashes, fecha, salida y código de retorno; `pruebas/resultados/phpunit.xml` contiene los resultados JUnit. Cualquier fallo, excepción, ausencia de PHPUnit, tiempo agotado o diferencia de conteo bloquea commit. No reutilizar un resultado obtenido antes del último cambio.

Ejecutor: PHPUnit 10.5.66 fijado en `pruebas/herramientas/composer.lock`, independiente del Composer de aplicación. Requisitos: Python 3, PHP 8.3, Composer y las extensiones que PHPUnit exige. Instalación sin plugins ni scripts. El XML descubre únicamente clases `*Test.php` bajo `pruebas/regresion`, con procesos separados y sin autoload/configuración de aplicación. Los dobles usan datos ficticios, sin conectar PDO ni servicios. PHPUnit conserva sus dependencias dentro de `pruebas/herramientas/vendor`, ignorado por Git; se reconstruyen con el lock.

Comando directo para diagnóstico, sin sustituir la puerta de registro:

    php pruebas/herramientas/vendor/bin/phpunit -c phpunit.xml

Registro central: `pruebas/regresion/registro.json`. Añadir cada suite nueva con casos, entradas, expectativa, número previsto y límites; actualizar configuración si cambia su ubicación. Si falla o cambia una expectativa, discutir con Martín si es regla de negocio o error. No usar omisiones ni aceptar pruebas incompletas. Los scripts iniciales se conservan fuera de esta copia como evidencia de caracterización; el único conjunto activo nuevo es PHPUnit.

## Alcance y decisiones
Esta primera batería cubre contratos unitarios de modelos y controladores FC factura, nota de crédito y complemento de pago. No equivale a pruebas completas de integración, persistencia, navegador, timbrado, pagos, cancelaciones o PDF. Antes de despliegue verificar respaldo recuperable, compatibilidad, pruebas pertinentes y plan de recuperación. Nunca ejecutar operaciones reales con esta batería. Los fallos o cambios de expectativas requieren discusión con Martín; no adaptar pruebas para ocultar errores.

## Pruebas anteriores
Inventario adjunto: 78 archivos de main `14e8dbaf6e3ad70797315fdc28240228e7f1acdc`. Se retiraron del conjunto activo de esta copia 72 archivos de `tests/` y la caché PHPUnit. No se eliminaron definitivamente. Respaldo completo `pruebas-anteriores.tar.gz`, SHA256 `bf6dc11f339fd16f31348c729a7cdda7015431ec0d538d3320ac269c5b4f1661`; cada contenido se verificó contra el inventario antes del traslado. El origen y producción no se modificaron. Cinco scripts test_* de herramientas de migraciones permanecen en su ubicación, fuera del ejecutor nuevo, pendientes de revisión específica; no se han ejecutado ni retirado.

Para recuperar las pruebas anteriores, comprobar SHA256 del archivo y de cada entrada del inventario, y extraer el tar en otra copia aislada vacía. No extraer sobre producción ni sobre trabajo sin respaldo. El respaldo está conservado externamente a esta copia y debe custodiarse junto al registro antes de integrar el cambio.

## Diferencia detectada en Partida
La expectativa original CP incluye `total` y `total_retenciones`. main verificado conserva ambos. El candidato de rendimiento Partida los omitía y falla ese contrato. No se cambió la expectativa; no integrar ese candidato sin resolver y discutir la diferencia. Rendimiento permanece fuera del alcance de esta entrega.

## Estado de integración
No hay commit, push ni despliegue. No se instalaron hooks ni se modificó Composer de aplicación. Las dependencias de desarrollo aisladas se fijan en herramientas/composer.json y composer.lock: el control previo a commit queda explícito mediante este ejecutor y la regla del proyecto. Las referencias técnicas originales se conservan cuando son identificadores de código.

## Capas posteriores
La integración con PAC sandbox y CSD de prueba será una batería separada, explícitamente autorizada. No forma parte de estos contratos unitarios ni requiere aquí secretos, red o certificados. No se transmitió información al PAC.
