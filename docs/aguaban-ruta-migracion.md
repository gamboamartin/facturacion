# Instalación del CRUD de rutas Aguaban

El catálogo utiliza `aguaban_ruta` con los once campos, los tipos BIGINT con signo, los índices y la relación `adm_usuario_id → adm_usuario.id` solicitados. No requiere campos de los catálogos genéricos como `codigo_bis` o `descripcion_select`. La migración no inserta rutas ni asigna operadores; las columnas de auditoría de las rutas las gestiona el CRUD.

## Preparar desarrollo

Desde `/var/www/html/facturacion`, con PHP 8.3, la configuración privada local y permisos DDL/DML del entorno de pruebas:

```sh
php tools/migrations/aguaban_ruta.php --database=facturacion
php tools/migrations/aguaban_ruta.php --database=facturacion --development --apply
```

El primer comando es un plan de solo lectura. El segundo exige expresamente la base `facturacion` y un host local; el instalador no aplica en los servidores de producción. No cargar estos archivos desde una vista, un modelo ni el arranque de la aplicación. El helper independiente `AguabanRutaMigracion::preparar(PDO $pdo, bool $aplicar = false)` permite verificar la migración en el motor efímero del laboratorio sin configuración privada.

El plan comprueba la compatibilidad de `adm_usuario.id`, la identidad administrativa, el namespace y las plantillas del catálogo antes de crear tablas. Si `aguaban_ruta` ya existe, valida columnas, nulabilidad, defaults, collation, autoincremento, fecha de actualización, índices y FK. Una incompatibilidad bloquea el instalador con un error; no se altera, vacía ni elimina la tabla existente. Requiere InnoDB para la relación y para los catálogos de permisos.

La aplicación adquiere un bloqueo específico de la base. MySQL confirma DDL implícitamente, por lo que la tabla se crea fuera de la transacción del catálogo después de todas las comprobaciones previas. Menú, sección, pertenencia al sistema, acciones y permisos se insertan juntos en una transacción. Si falla esa fase se revierten sus inserciones; puede reintentarse conservando la tabla vacía creada. La migración no modifica filas de rutas ni catálogos ajenos.

## Menú y permisos

La primera ejecución crea el menú `Aguaban` (`codigo=aguaban`), la sección `aguaban_ruta` dentro del namespace activo `gamboa.martin/facturacion` y una pertenencia activa al sistema `facturacion`, identificado por código y descripción y contrastado con la sección de facturas. La tabla `adm_seccion_pertenece` es parte del catálogo transaccional: permite que la navegación real del framework encuentre el nuevo menú. También crea nueve acciones:

- GET: `lista`, `alta`, `modifica`, `consulta`, `elimina` (confirmación).
- POST: `alta_bd`, `modifica_bd`, `elimina_bd`, `status`.

Las nueve acciones tienen seguridad activa. Todas permiten vista para mostrar errores de formulario; las escrituras correctas redirigen y las vistas proxy no convierten GET en escrituras. El controlador impone método HTTP, CSRF, lista cerrada de acciones y autorización en el servidor. El listado no necesita un endpoint AJAX adicional.

Los permisos se conceden exclusivamente al grupo activo que coincida simultáneamente con `codigo=PA` y `descripcion=Administrador Sistema`; debe existir exactamente uno. No se codifican IDs, no se confunde con el grupo `Sistema` y no se autorizan operadores por el simple hecho de estar asignados a una ruta. Los IDs de usuario de auditoría del catálogo se conservan de la sección `fc_factura` utilizada como plantilla, siguiendo la convención de estas migraciones.

En ejecuciones posteriores, una sección existente sin pertenencia al sistema, con pertenencias duplicadas o asociada a otro sistema bloquea el instalador. No se reconstruye la relación automáticamente porque su eliminación puede ser una revocación. Una pertenencia inactiva tampoco se reactiva. Una sección existente solo se valida: no se reactivan acciones ni se reconstruyen acciones o permisos eliminados. Tampoco se reponen permisos inactivos. Si se encuentran permisos activos para otros grupos o acciones sin seguridad activa, se bloquea la preparación y se informa para revisión; no se revocan permisos globales automáticamente. La autorización del controlador exige además que el usuario actual siga perteneciendo al grupo administrativo activo y tenga activa su acción concreta.

La instalación local de este CRUD y su validación no implican despliegue en Aguaban ni otros destinos. Antes de cualquier aplicación en producción se requiere el despliegue expresamente solicitado y un respaldo del esquema y del catálogo de seguridad del destino.
