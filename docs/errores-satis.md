# Errores v6.3.0 mediante Satis

La migración conserva la versión v6.3.0 y el commit 7edeeff2d3e63a287f17fd4340b0012faff0aa74. El código de ejecución no cambia: se modifica únicamente su distribución, desde el ZIP de Satis con checksum y sin fallback source a GitHub.

## Consumidores

Cada aplicación raíz debe incluir `gamboa.martin/errores` en el `only` del repositorio canonical `https://paquetes.ivitec.mx` y renovar su lock. Los repositorios declarados por una biblioteca no se heredan; las bibliotecas que ya requieren ^6.0.0 no necesitan nuevos tags por este cambio. Las credenciales van en el almacén privado de Composer, fuera de Git.

Facturación incluye esa configuración y fija la misma versión en composer.lock. El paquete está publicado junto a configuraciones, plugins, documento y administrador; los artefactos previos se conservan.

## Validación local

El tag, el ZIP oficial y el archivo src/errores.php instalado se contrastaron por contenido. Se ejecutó el test propio del paquete con configuración de bitácora temporal aislada: una prueba, seis aserciones. La batería completa de Facturación aprobó sus 14 etapas, con 178 pruebas y 807 aserciones unitarias, integración ORM con MariaDB efímera, baja CSD, FPM local aislado y comprobaciones de frontend. Se usan datos sintéticos; no acredita operaciones fiscales reales ni instalaciones remotas.

## Secuencia de despliegue y privacidad

Por instrucción del propietario, primero se valida e instala en la máquina local. Cada despliegue remoto se realiza cuando indique el destino; el estado local no equivale a un deployment en servidores.

GitHub permanece público y Packagist conserva sus versiones mientras existan consumidores legacy pendientes. Se encontraron instalaciones que usan errores 2.x, 4.x y otras 6.x con locks desde GitHub. No actualizar sus versiones mayores automáticamente. Antes de privatizar el repositorio completo hay que publicar sus versiones exactas en Satis, migrar sus raíces/locks, comprobar autenticación y red y validar cada destino aprobado. Retirar únicamente v6.3.0 o cambiar la privacidad sin cubrir los consumidores anteriores no constituye una migración compatible.

No mover el tag v6.3.0 ni reemplazar el contenido de ZIP publicados. Las instrucciones operativas, inventarios de instalaciones y credenciales se mantienen fuera del repositorio.
