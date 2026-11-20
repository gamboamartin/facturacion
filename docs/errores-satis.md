# Errores v6.3.0 mediante Satis

La migración conserva la versión v6.3.0 y el commit 7edeeff2d3e63a287f17fd4340b0012faff0aa74. El código de ejecución no cambia: se modifica únicamente su distribución, desde el ZIP de Satis con checksum y sin fallback source a GitHub.

## Consumidores

Cada aplicación raíz debe incluir `gamboa.martin/errores` en el `only` del repositorio canonical `https://paquetes.ivitec.mx` y renovar su lock. Los repositorios declarados por una biblioteca no se heredan; las bibliotecas que ya requieren ^6.0.0 no necesitan nuevos tags por este cambio. Las credenciales van en el almacén privado de Composer, fuera de Git.

Facturación incluye esa configuración y fija la misma versión en composer.lock. El paquete está publicado junto a configuraciones, plugins, documento y administrador; los artefactos previos se conservan.

## Validación local

El tag, el ZIP oficial y el archivo src/errores.php instalado se contrastaron por contenido. Se ejecutó el test propio del paquete con configuración de bitácora temporal aislada: una prueba, seis aserciones. La batería completa de Facturación aprobó sus 14 etapas, con 178 pruebas y 807 aserciones unitarias, integración ORM con MariaDB efímera, baja CSD, FPM local aislado y comprobaciones de frontend. Se usan datos sintéticos; no acredita operaciones fiscales reales ni instalaciones remotas.

## Secuencia de despliegue y privacidad

Por instrucción del propietario, primero se valida e instala en la máquina local. Cada despliegue remoto se realiza cuando indique el destino; el estado local no equivale a un deployment en servidores.

El 20 de noviembre de 2026, por instrucción explícita del propietario, GitHub se cambió a privado tras validar Facturación local con Satis. La descarga sin caché, el test del paquete y las 14 etapas de Facturación se aprobaron con GitHub privado. Satis dispone de acceso de lectura al repositorio privado y se verificó la construcción sin reemplazar el ZIP ya publicado. El webhook GitHub → Packagist quedó desactivado; las versiones de Packagist no se retiraron en este paso.

Los consumidores legacy que usan errores 2.x, 4.x y otras 6.x con locks desde GitHub siguen pendientes. Su capacidad de reinstalar desde esos locks no queda acreditada y puede requerir migración antes de una nueva instalación. No actualizar sus versiones mayores automáticamente: publicar sus versiones exactas en Satis, migrar sus raíces/locks y validar autenticación, red y comportamiento en cada destino que autorice el propietario. La validación local no acredita compatibilidad en esos servidores.

No mover el tag v6.3.0 ni reemplazar el contenido de ZIP publicados. Las instrucciones operativas, inventarios de instalaciones y credenciales se mantienen fuera del repositorio.
