# Test v5.0.0 mediante Satis

## Contrato y distribución

Se conserva el tag v5.0.0 y el commit `4f72a308d5fcb56e5290ccbbc91c49d2294ce123`, sin modificar código de ejecución. Los diez archivos del ZIP oficial, el paquete local anterior y el paquete de Satis se contrastan por contenido. El ZIP publicado tiene checksum y el lock consumidor apunta a Satis sin fallback source a GitHub.

La aplicación raíz incluye `gamboa.martin/test` en `repositories[].only` de Satis canonical y renueva el lock. Las bibliotecas no heredan repositorios Composer: no necesitan nuevos tags si sus restricciones ya admiten v5.0.0. Mantener autenticación fuera de Git.

## Validación

El ensayo usa una copia local aislada de Facturación. El update dirigido debe cambiar únicamente la metadata de test, conservando versiones y referencias de los demás paquetes y los bytes del paquete. Se verifica descarga sin caché y reinstalación del paquete con Packagist deshabilitado en la copia de prueba.

La suite `pruebas/test_satis/ejecutar.py` ejercita 16 contratos de liberator y de registro con las fuentes reales y PHPUnit aislado. No llama al constructor de la base test, que conecta PDO/mysqli, ni carga configuración activa. Esa conexión no queda acreditada por la suite focal. La batería registrada completa añade integración ORM/MariaDB efímera, baja CSD/empresa, FPM aislado, unitarias y frontend; no acredita PAC ni operaciones fiscales autenticadas.

## Operación y alcance

Primero se valida e instala en la máquina local. Los despliegues remotos se realizan exclusivamente cuando el propietario indique cada destino. La publicación del paquete en Satis no equivale a un despliegue de Facturación.

No mover el tag v5.0.0 ni reemplazar artefactos publicados. Satis conserva los paquetes y archivos previos y publica el catálogo mediante cambio atómico de release. Se configura acceso de lectura de Satis al repositorio privado y se verifica su constructor sin volver a publicar el ZIP.

Los consumidores con locks antiguos desde GitHub o versiones previas a v5.0.0 quedan pendientes: deben migrar su raíz/lock y comprobar acceso antes de una nueva instalación. La validación local no acredita esas instalaciones. El retiro de estables en Packagist es recuperable; la eliminación permanente de dev-main es una operación distinta.

Resultado local del 20 de noviembre de 2026: 16 etapas aprobadas; 178 pruebas unitarias/807 aserciones y 16 contratos de test/79 aserciones, además de integración y regresión registradas. Instalación local idéntica al candidato, plataforma compatible y acceso HTTP 200. Solo cambia la distribución de test; se conserva v5.0.0 y las versiones/referencias de los demás paquetes. Se retiró también la notificación de descargas a Packagist de la metadata de test.

Estado final verificado el 20 de noviembre de 2026: repositorio GitHub privado, webhook hacia Packagist desactivado y Satis con acceso de lectura mediante llave dedicada. El constructor desde GitHub privado aprobó y sus diez archivos coinciden con el tag original; ese ensayo no reemplazó el ZIP publicado. Se repitieron descarga sin caché con Packagist deshabilitado y las 16 etapas con GitHub privado: PASS.

En Packagist se retiraron las siete versiones estables mediante soft-delete y se eliminó permanentemente dev-main con confirmación explícita del propietario. Tras recargar la UI se verificaron siete opciones de recuperación y ausencia de dev-main; la ficha histórica permanece. Una nueva descarga aislada de test desde Satis pasó después del retiro. No se aplicaron despliegues remotos de Facturación.
