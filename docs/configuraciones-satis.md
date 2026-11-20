# Configuraciones mediante Satis

## Estado de la migración

Verificado el 20 de noviembre de 2026:

- `gamboamartin/configuraciones` es privado en GitHub.
- Satis publica `gamboa.martin/configuraciones v1.0.0`, con su ZIP alojado en `https://paquetes.ivitec.mx` y el contenido original conservado.
- El webhook GitHub → Packagist está desactivado.
- En Packagist se retiró `v1.0.0` mediante soft-delete y se eliminó `dev-main`. La ficha histórica permanece; la versión estable es recuperable por el mantenedor.
- Se verificaron instalaciones sin caché y con Packagist deshabilitado en Facturación local, facturacion.ivitec.mx, FC3 y Gutell. Después del retiro se repitió la descarga aislada en MX, con éxito.
- En MX se aplicó Composer update general con respaldo: `guzzlehttp/promises` pasó de 2.6.0 a 2.6.1; `configuraciones` se mantuvo en v1.0.0. Se aprobaron requisitos PHP y comprobaciones de acceso/recursos web. No se ejecutaron flujos fiscales autenticados; la prueba ORM por CLI quedó sin acreditar al detenerse en un constructor que requiere contexto de sesión.

Estos resultados cubren los destinos comprobados; no acreditan instalaciones desconocidas. Las pruebas de distribución y acceso web no sustituyen pruebas fiscales, autenticación de usuarios ni integración con PAC.

## Configuración de consumidores

El `composer.json` raíz debe declarar Satis como repositorio canonical antes de Packagist, limitado a los paquetes privados. La configuración existente de Facturación incluye `gamboa.martin/configuraciones` en `repositories[].only`. Composer no hereda repositorios declarados por dependencias.

El acceso requiere red autorizada y autenticación HTTP Basic. Guardar credenciales fuera del repositorio, en el almacén de autenticación de Composer del operador o un gestor de secretos; no introducirlas en URLs, lockfiles, instrucciones ni reportes publicados.

En una copia aislada, resolver el paquete desde Satis y comprobar que su entrada en `composer.lock` contiene una URL `dist` del dominio de Satis. Un lock antiguo puede seguir apuntando a GitHub; agregar el repositorio por sí solo no modifica ese lock. Para comprobar la independencia de Packagist, usar una instalación aislada del paquete con Packagist deshabilitado y sin caché.

## Publicaciones y despliegues posteriores

Satis dispone de acceso de lectura al repositorio privado. El constructor privado se verificó con PHP 8.5; esta versión corresponde a la herramienta Satis, no cambia el requisito PHP 8.3 de Facturación.

Publicar versiones nuevas con tags nuevos y artefactos verificables. Conservar las versiones, ZIP e índices previos; no reemplazar el contenido de una versión existente. Construir en staging y fusionar el resultado con el catálogo completo antes de activar una nueva release de forma atómica con respaldo. Un build de un solo paquete no debe sustituir el catálogo de los cuatro paquetes.

Antes de activar dependencias, comprobar plataforma, alcance de cambios y pruebas pertinentes. Respaldar vendor y lock, preservar configuración/documentos del proyecto, preparar reversión y verificar la aplicación después de la activación. Las credenciales y los detalles de infraestructura se custodian en documentación operativa privada.

## Permisos de red

Por instrucción del propietario, los segmentos autorizados en Satis permanecen hasta que solicite expresamente su baja. Las nuevas IP se agregan conservando los permisos anteriores. Revisar tanto la lista de Nginx como el firewall HTTPS; cada ampliación debe corresponder al alcance autorizado. No inferir ni agregar redes por iniciativa propia.
