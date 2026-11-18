# Previsualización autenticada de logos: candidato en copia

## Estado al preparar el cambio

Main remoto y base de copia siguen en `9dfc6f3d821db5c7801f0278acbe7f10a1cfce83`. El branding principal ya está aplicado y revisado en FC3; este ajuste adicional de preview **no está desplegado ni publicado**.

Solo cambia `controllers/controlador_org_logo.php` y `views/org_logo/subir_logo.php`, reutilizando el lector limitado del branding ya instalado. No altera selección principal, formulario POST, método de activación, archivos de imagen, SQL, Nginx, PDF, credenciales ni permisos. El logo anterior ID 4 fue retirado por el coordinador mediante la interfaz autorizada; el actual es ID 5.

## Autoridad y asociación

La ruta vigente del dispatcher carga y valida sesión antes de verificar la ACL de sección/acción/grupo. En `adm_session::asigna_datos_session` se valida el registro de sesión y después se asignan `activa=1`, `grupo_id` y `usuario_id`. `numero_empresa=1` es una constante histórica y **no se usa** como autorización.

El modelo de esta acción permite consultar el catálogo global de logos por la ACL `org_logo/subir_logo`; no se inventa una restricción de tenant que el sistema no implementa. Para añadir bytes a la vista se requiere de nuevo esa misma ACL, mediante `adm_accion::permiso(accion: 'subir_logo', seccion: 'org_logo')`, cuyo contrato real es `bool|array`. Solo el booleano estricto `true`, sin bandera de error y con sesión activa, usuario y grupo positivos, permite previsualizar. Un array de error no concede acceso.

Se conservan filtros activos, empresa seleccionada por el controlador y límite de 12 filas. Las consultas incorporan los alias de empresa, documento propio y unido y tipo documental. Cada fila debe tener ID de logo positivo, estado activo, empresa igual a la selección de esa acción, documento propio igual al documento unido y positivo, y tipo 9. Solo entonces el lector abre su ruta registrada dentro del almacén fijo canónico, validando tipo JPEG/PNG, tamaño y enlaces como antes.

Los bytes se añaden en el campo `imagen_privada_url` de las filas que el controlador ya decidió mostrar. No hay endpoint nuevo, lectura de ruta/ID documental desde GET ni helper público que busque logos históricos. La única elección por GET es la empresa que esta acción ya permitía consultar mediante su ACL global. La acción pública de branding continúa ignorando GET y nunca incorpora imágenes históricas.

Si `ws=true`, no se añaden data URI a la respuesta ni se ejecuta la comprobación adicional de preview. Si la fila, archivo o permiso no permite incrustar, la vista conserva su enlace estático anterior. Si no hay principal, `logo_activo` sigue siendo exactamente `[]`, no un array con un campo vacío. No se presenta el fallback como una solución al 404.

## Verificación

Registro central: 108 casos originales intactos, 27 de branding y 20 de preview. Última ejecución completa: **155 casos / 539 aserciones PASS**, **21 frontend PASS**, lint de controlador/vista PASS y `git diff --check` PASS.

Los nuevos casos ejecutan el controlador y render PHP de la vista reales, con modelos y checker ACL dobles documentados, archivos PNG ficticios propios y sin SQL/red. Cubren principal e histórico autorizado, ausencia/inactividad de sesión, usuario/grupo ausente, permiso denegado/error, WS sin bytes, empresa/documento/tipo/estado/ID incorrectos, GET de ruta/documento ignorado, traversal, MIME falso, filtros/columnas, render autorizado y fallback, y ausencia de principal.

La firma real de `adm_accion::permiso`, la representación de sesión y el generador de alias ORM `<tabla>_<columna>` se contrastaron con código activo. Los dobles no demuestran por sí solos el resultado SQL ni la autenticación real. No se han realizado aún revisión de navegador del preview candidato ni consultas reales con sus nuevas columnas. La validación visual pendiente debe ser de solo lectura, sin volver a activar/eliminar logos.

## Plan focal de despliegue, pendiente de aprobación y ejecución

Los dos archivos activos coinciden byte por byte con main, sin personalizaciones que sobrescribir; propietario `root:facturacion3`, modo 0644. Se preparó copia privada de respaldo y ensayo de restauración que verifica hashes, propietario, grupo y modo. Se comprobaron también los hashes del helper y lector ya activos como dependencias.

Plan: guardias de hashes y metadatos vigentes; timer de 45 minutos antes de reemplazar; sustitución atómica únicamente de controlador y vista; recarga exclusiva de `facturacion3-php`; verificación de hashes, servicio y HTTP 200; revisión UI de «Logo actual» y miniatura, con logo ID 5 sin mutación. Ante fallo, restaurar ambos archivos. El rollback no toca helper/lector del branding ni selección del logo. No detener el timer sin la autorización correspondiente y el resultado visual.

Hashes candidatos:

- Controlador: `7575f7d3939dcc14d50461658889b13c1149ba6715ab51073542a802542f5359`
- Vista: `431d3e8bf6e97bf27a6dec9d6dd743893a642bc21b0b938d2f75cd3ebb2f4dc2`

Hashes base respaldados:

- Controlador: `f50721e39fd9e53fad025fb884b097c8a18d59dce7c6d91d30360b092a688188`
- Vista: `fbcc26805c8ad31f03a02451f910a492f68de59a688ac9f5bb327bccf57ef012`

No hacer commit/push hasta revisión y repetición de la batería vigente inmediatamente antes, comprobando de nuevo main y trabajo concurrente. Una prueba fallida o no ejecutada bloquea publicación; no modificar expectativas para obtener un PASS.

## Aplicación posterior para revisión

Tras confirmación directa de Martín a las 16:11:15 UTC, el candidato se aplicó el 18 de noviembre a las **16:11:59 UTC**. Las guardias comprobaron de nuevo bases, candidatos y dependencias; la recarga exclusiva de `facturacion3-php`, hashes finales y HTTP 200 pasaron. El rollback de estos dos archivos quedó activo desde las 16:11:58 UTC y vencerá a las **16:56:58 UTC** salvo autorización posterior para cancelarlo. Los párrafos anteriores describen el estado de preparación, no un despliegue aún pendiente.

El coordinador realizará la revisión visual de solo lectura. Este agente no ejecutó acciones UI, SQL ni cambió la selección del logo. Todavía no hay commit/push.

## Revisión visual posterior: PASS con límites explícitos

El coordinador verificó en FC3 el logo principal real ID 5 en «Logo actual» y su miniatura administrativa: ambos usan data URI y muestran LIAM con dimensiones naturales 1358 × 1158. Encabezado, pie y login público también pasaron. La navegación «Volver» a lista (única fila ID 5) y regreso a «Subir Logo» conservó la previsualización. No se crearon nuevos registros ni se realizaron operaciones de negocio.

Una espera de Playwright agotó su tiempo antes de que DataTables completara la carga; la inspección posterior confirmó la fila y la navegación normal pasó. No se cambió ninguna expectativa ni se diagnosticó un fallo funcional a partir de ese timeout.

Alcance visual real: sesión admin autorizada y login público. No se inició otra cuenta ni se validó visualmente un logo histórico/no principal real; ese caso está cubierto únicamente por ensayo sintético. Las capturas son las emitidas en línea por el coordinador, no archivos entregables guardados por este agente. La cancelación del timer y la publicación requieren el cierre coordinado correspondiente; no se realizaron como parte de la revisión.

## Cierre autorizado del despliegue

Martín confirmó conservar la corrección y publicar los cambios. A las **16:18:51 UTC** se canceló el timer de preview, verificando estado inactivo, los cuatro hashes runtime, ambos archivos de respaldo, servicio activo y HTTP 200. Los respaldos privados se conservan. La programación de las 16:56:58 UTC descrita antes es histórica: ya no hay reversión pendiente para este cambio.

La publicación conjunta comprende branding, preview, sus pruebas registradas y documentación. No incluye el prototipo histórico aislado ni herramientas/resultados temporales. El despliegue de FC3 fue focal por archivos: no equivale a actualizar el HEAD Git de la carpeta activa. Antes de publicar se vuelve a ejecutar la batería completa y se consulta main remoto; no se publica si fallan o aparecen cambios concurrentes incompatibles.
