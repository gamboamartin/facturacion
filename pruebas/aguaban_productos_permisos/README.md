# Permisos de productos en Aguaban

El instalador es explícito y no se ejecuta desde vistas, constructores ni despliegues genéricos. Requiere el perfil y usuario activos `administracion_clientes`, con `root=inactivo`, y el administrador existente `PA / Administrador Sistema`.

Agrega las ocho acciones CRUD de `com_producto` y `com_tipo_producto`, más la búsqueda SAT `cat_sat_cve_prod/get_data_descripcion`: 17 permisos nuevos de clientes. Si falta `com_producto/status`, crea esa acción segura y autoriza también al administrador. Protege con `seguridad=activo` el endpoint existente `com_tipo_producto/get_data`. Los permisos anteriores no se modifican; un permiso existente revocado provoca un rechazo explícito.

En el estado verificado, clientes pasa de 27 a 44 permisos; se insertan 18 filas ACL contando el nuevo permiso administrativo, se crea una acción y se cambia la seguridad de una acción existente. No se crean usuarios, grupos, productos, tipos, documentos ni tablas.

## Operación explícita

Plan sin escrituras:

```sh
php tools/migrations/aguaban_productos_permisos.php --database=aguaban
```

Aplicación, después del respaldo y la comprobación de destino:

```sh
php tools/migrations/aguaban_productos_permisos.php --database=aguaban --production-vpn --apply --report=/srv/aguaban-produccion-preparacion/productos/diario.json
```

La ruta del diario debe ser nueva, absoluta y estar fuera de la aplicación. El instalador escribe el diario completo, sincroniza archivo y directorio antes de confirmar la transacción. Si una confirmación o la escritura posterior falla, conserva el diario pendiente para verificar o revertir las filas por sus IDs y hashes.

Reversión de los cambios propios y sin modificaciones posteriores:

```sh
php tools/migrations/aguaban_productos_permisos.php --database=aguaban --production-vpn --rollback=/srv/aguaban-produccion-preparacion/productos/diario.json
```

La reversión rechaza permisos o acciones modificados por terceros y acciones nuevas que otros grupos ya estén usando. No elimina filas preexistentes. Los contadores `AUTO_INCREMENT` pueden avanzar; no ejecuta DDL para restablecerlos.

## Regresión con motor SQL real

```sh
QA_PRODUCTOS_SECRET_FILE=/ruta/privada/sql-qa-secret.json python3 pruebas/aguaban_productos_permisos/ejecutar.py
```

El archivo privado usa claves `dsn`, `user`, `password`; también se admiten variables `QA_PRODUCTOS_DSN`, `QA_PRODUCTOS_DB_USER`, `QA_PRODUCTOS_DB_PASSWORD`. Nunca se leen las credenciales de la aplicación.

El esquema debe estar vacío y llamarse `qa_productos_permisos_*`; se recomienda un usuario limitado exclusivamente a ese esquema. La prueba crea siete tablas ficticias InnoDB y las elimina al terminar, incluso ante fallos. Comprueba 17 casos y 33 aserciones de alcance, aplicación, idempotencia, diario previo al commit y recuperación ante fallo del segundo guardado posterior, preservación de originales y reversión ante conflictos. Una conexión de prueba conserva su destino real de QA, pero declara el nombre `aguaban` exclusivamente para comprobar que `--development` no permite ese nombre ni abre una transacción.

La suite no carga configuración real, controladores, formularios ni PAC. La integración del esquema completo y la interfaz se verifican por separado en el laboratorio UI.
