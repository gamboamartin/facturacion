# SQL v1.4.0 mediante Satis

## Distribución y compatibilidad

Se conserva el tag v1.4.0 y su commit `37d36cbf39079a94abfe37f2c25fe39934d38a13`. Los seis archivos del ZIP oficial, el paquete instalado y el artefacto de Satis coinciden por SHA256. No se modifica el código SQL ni sus contratos; únicamente la distribución.

La raíz de Facturación incluye `gamboa.martin/sql` en `repositories[].only` del repositorio canonical `https://paquetes.ivitec.mx`, junto a test y errores. El lock cambia solo la metadata de SQL: ZIP de Satis con checksum, sin fallback source a GitHub y sin notificación de descargas a Packagist. Los repositorios de dependencias no se heredan. Mantener credenciales fuera de Git.

`where` requiere SQL y `administrador` también utiliza directamente `gamboamartin\src\sql`. Se conservan el namespace compartido y sus rutas PSR-4. No se requiere un tag nuevo de los consumidores si admiten v1.4.0.

## Verificación y límites

Validar en una copia local aislada: resolución dirigida de SQL, reinstalación sin caché con Packagist deshabilitado, comparación completa de archivos, plataforma y batería registrada de Facturación. Las pruebas usan datos sintéticos y no cargan configuración privada ni ejecutan PAC.

La migración conserva también las limitaciones anteriores del helper; no es un mecanismo de escape ni de consultas preparadas. Cualquier cambio funcional futuro debe evaluarse por separado y publicarse con un tag nuevo.

## Publicación y despliegues

Publicar en staging y fusionar el catálogo conservando los artefactos e índices anteriores, activando mediante enlace atómico. No mover tags ni reemplazar ZIP ya publicados. Satis dispone de llave de lectura dedicada para el repositorio privado; su constructor se verifica sin reemplazar el ZIP publicado.

Primero se valida e instala en Facturación local. Los despliegues remotos requieren que el propietario indique cada destino. Consumidores con locks desde GitHub o versiones anteriores a v1.4.0 quedan pendientes de migración antes de nuevas instalaciones; no se actualizan sus versiones mayores automáticamente.

Resultado local del 20 de noviembre de 2026: 17 etapas PASS; 178 pruebas unitarias/807 aserciones y 21 contratos SQL/consumidores/89 aserciones. Instalación local idéntica al candidato y acceso HTTP 200 con formulario. No se actualizaron otras dependencias. La suite focal conserva y documenta dos defectos preexistentes: el tratamiento de entradas compuestas solo por blancos puede producir IN vacío, y la normalización de espacios puede alterar literales. Su reparación requiere otra revisión/tag; no se ocultan como correcciones de esta migración.
