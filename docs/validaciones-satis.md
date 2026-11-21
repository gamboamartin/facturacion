# Validaciones v1.3.0 mediante Satis

## Contrato y distribución

El paquete plural conserva el tag v1.3.0 y el commit `99a069c9c8ac8014311b0b143d6411c6209ec049`. Los seis archivos del ZIP oficial, el vendor previo y el ZIP rearchivado de Satis se verifican por contenido. No se cambia el método, su herencia, su namespace compartido con SQL ni sus restricciones de dependencias.

La raíz de Facturación añade `gamboa.martin/validaciones` a `repositories[].only` de Satis canonical. Composer renueva solo la metadata de ese paquete: dist HTTPS de Satis con checksum, sin fallback source a GitHub ni notificación de descargas a Packagist. La configuración de repositorios no se hereda de bibliotecas; mantener credenciales fuera de Git.

`where` utiliza directamente `gamboamartin\src\validaciones`, que hereda de `gamboamartin\validacion\validacion` (paquete singular). Administrador tiene además su clase distinta `gamboamartin\administrador\modelado\validaciones`; no se reemplaza por la del paquete plural. Las pruebas distinguen ambos flujos.

## Validación

Resolver e instalar en una copia aislada de Facturación, preservar versiones/referencias de los demás paquetes, comparar los seis archivos originales y repetir reinstalación sin caché con Packagist deshabilitado. Ejecutar plataforma y batería registrada completa antes del commit y de activar el entorno local.

La suite `pruebas/validaciones_satis/ejecutar.py` comprueba contrato, herencia singular, namespace/autoload compartido y consumidores reales con datos sintéticos sin cargar configuración privada, sesiones activas ni BD. La batería complementaria usa integración ORM con motor efímero; no acredita PAC ni flujos fiscales reales.

El método admite valor omitido porque lo agrega a una copia local; no modifica el argumento original. Los consumidores que necesitan valor lo validan posteriormente. Ese contrato se conserva, sin convertir la migración en una corrección funcional.

## Publicación y alcance

La publicación se construye en staging y fusiona el catálogo conservando artefactos/índices anteriores; la activación de Satis usa enlace atómico con bloqueo de publicación. No mover tags ni reemplazar ZIP publicados. Se prepara una llave de lectura de Satis para el repositorio privado y se verifica el constructor sin volver a publicar el ZIP.

Primero se instala y valida en desarrollo local. Los despliegues remotos de aplicaciones se realizan solo cuando el propietario indique cada destino. Los consumidores con locks desde GitHub y versiones anteriores a v1.3.0 quedan pendientes de migración antes de nuevas instalaciones, conservando sus versiones mayores.

Resultado local del 20 de noviembre de 2026: PASS de las 18 etapas registradas, con 178 pruebas unitarias/807 aserciones y 26 casos nuevos de validaciones/113 aserciones. Plataforma compatible, descarga sin caché con Packagist deshabilitado y comparación de los seis archivos del tag original. El vendor local coincide con el candidato probado, se preservaron configuración/plantillas y el acceso local responde HTTP 200 con formulario de login. No cambiaron versiones ni referencias de otros paquetes.

## Estado final de privacidad y retiro

Verificado el 20 de noviembre de 2026: GitHub privado, webhook hacia Packagist desactivado y constructor Satis desde el repositorio privado aprobado con los seis archivos originales. Se repitieron la descarga sin caché con Packagist deshabilitado y las 18 etapas con GitHub privado: PASS.

Las cuatro versiones estables de Packagist se retiraron mediante soft-delete y dev-main se eliminó permanentemente con confirmación explícita del propietario. Tras actualizar la ficha se verificaron ausencia de dev-main y cuatro opciones de recuperación de estables; la ficha histórica permanece. Una descarga aislada posterior al retiro completo pasó desde Satis y conservó los seis archivos originales. No se aplicaron despliegues remotos de aplicaciones.
