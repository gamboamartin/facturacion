# Logo principal: candidato aislado del 18 de noviembre de 2026

## Estado y alcance

Base verificada contra `origin/main`: `9dfc6f3d821db5c7801f0278acbe7f10a1cfce83`.
Al preparar el candidato no se hizo commit, push ni despliegue. La aplicación posterior autorizada para revisión se registra al final. No se modificaron datos, permisos, Nginx ni PDF.
El candidato modifica `templates/logo_service.php` y añade `templates/logo_principal_lector.php`.
Login, menú y pie ya llaman al helper: no se editaron esas vistas ni se añadió un requisito de administración de logos.

El logo principal es branding que ya mostraban esas páginas, incluso a visitantes del login y usuarios sin permiso `org_logo/subir_logo`. El lector nuevo no es un endpoint público para descargar documentos. Nunca recibe empresa, logo, documento ni ruta de GET. Los logos históricos o no principales no se convierten en imágenes embebidas.

## Selección y límites de lectura

1. Consultar hasta dos empresas activas por ID ascendente. No fijar nombre o ID de empresa en código.
2. Consultar hasta dos logos activos y principales de la primera empresa, por ID descendente.
3. Resolver el documento asociado mediante su ID obtenido del modelo.
4. Solo incrustar bytes si hay exactamente una empresa y un principal, la asociación de empresa coincide y el documento tiene tipo 9.
5. Leer exclusivamente `archivos/doc_documento/<nombre>` dentro del almacén canónico de la aplicación. Rechazar rutas alternativas, protocolos, traversal, archivos ocultos y enlaces simbólicos, también en los padres del almacén.
6. Contrastar dispositivo/inodo del archivo abierto con el comprobado, exigir archivo regular, máximo 2 MiB y MIME real JPEG o PNG coincidente con sus dimensiones. Emitir `data:image/...;base64`.

Ante ambigüedad, tipo distinto o rechazo del lector, conservar el enlace estático histórico del principal seleccionado sin leer sus bytes. Si faltan registros o falla el modelo, devolver `null`, como el manejo de error anterior. El fallback ambiguo usa la primera empresa activa, nunca una empresa escogida por GET. Esto elimina el selector de branding por parámetro; no modifica las pantallas de administración de empresas.

La comprobación de asociación no inventa un aislamiento por empresa de usuario. El modelo vigente de logos utiliza ACL de sección/acción/grupo y consultas explícitas sin seguridad por fila. El estado del documento no incorpora un filtro nuevo: se conserva la consulta histórica por ID, exigiendo tipo 9 para los bytes; el logo sí debe estar activo y ser principal.

El lector verifica tipo y límite de bytes; no recodifica imágenes ni sanea metadatos. El principal seleccionado debe ser una imagen apta para branding público. El almacén y sus escritores de aplicación siguen siendo confiables; no se afirma resistencia a un administrador malicioso que altere el archivo durante la lectura. No se cambia caché entre usuarios: la caché estática existe solo durante la petición, con el mismo branding principal para todos.

## Evidencia de compatibilidad por lectura

- CSP vigente de FC3 admite `img-src 'self' data:`. No hace falta relajar esta política.
- `doc_documento.php` construye `archivos/<tabla>/` y añade el nombre de archivo (líneas 97/135 y 336/353): sin slash inicial.
- El ORM `base/orm/columnas.php:2466` genera alias como `<tabla>_<columna>`, incluyendo `org_logo_org_empresa_id` y `doc_documento_doc_tipo_documento_id`.
- `templates/nav/_menu_superior.php:133`, `templates/nav/_redes_sociales.php:33`, `templates/footer/_footer_ivitec.php:21` y `views/adm_session/login_ivitec.php:12` usan el helper. Menú y pie admiten previamente `controlador->logo_empresa_url`; no se encontraron asignaciones de ese override en controladores de aplicación ni en los controladores de administrador, organigrama y system inspeccionados. Un override añadido posteriormente podría evitar el helper y debe revisarse.
- La carga nueva de `fileinfo` en el perfil PHP de pruebas aislado permite comprobar MIME real; se conservaron `open_basedir`, directorio temporal y restricciones de red. No se instaló software ni se cambió PHP del servidor. Antes de desplegar se debe verificar esa extensión también en el FPM efectivo.

## Pruebas reproducibles

Registro central: `pruebas/regresion/registro.json`. Se conservaron los 108 casos anteriores y sus expectativas; se agregaron 27 casos de `LogoPrincipalTest`.

```sh
python3 pruebas/regresion/ejecutar_phpunit.py
for prueba in pruebas/frontend/*/probar_*.py; do
    python3 "$prueba" || exit 1
done
```

El entorno de ejecución debe tener PHPUnit fijado por `pruebas/herramientas/composer.lock`, PHP con `fileinfo`, y un directorio temporal escribible. Las pruebas usan únicamente modelos dobles, consultas registradas sin SQL y archivos sintéticos. El perfil aislado utilizado deshabilita conexiones de red y restringe la lectura al checkout.

Resultado final: **135 casos, 470 aserciones PASS; 21 pruebas frontend PASS; cuatro archivos PHP con lint PASS; `git diff --check` PASS.**

Casos nuevos: branding anónimo/login, autenticado sin administración y administrador; independencia de GET; empresa/principal ambiguos; asociación incorrecta; tipo diferente; ausencia y errores; PNG y JPEG reales; traversal/protocolos; enlaces de archivo y almacén; almacén no canónico; SVG renombrado; tamaño vacío, superior y exacto al límite; archivo oculto; caché e independencia de sesión; referencias existentes desde vistas.

Las referencias de vistas se verifican estáticamente. No equivalen a render completo ni a una revisión visual del candidato en navegador. La carga ORM real no se ejercita contra una base: los alias se contrastaron con código, no con SQL.

## Incidencia del primer ensayo, conservada sin ocultarla

La primera ejecución terminó **BLOQUEADO**: 134 casos, 467 aserciones, ninguna aserción fallida y una advertencia. El diagnóstico con `--display-warnings` identificó `LogoPrincipalTest::testLectorRechazaArchivoOculto`: el `tearDown` utilizaba `glob('*')`, no retiraba `.oculto.png`, y `rmdir` advertía `Directory not empty`.

Se corrigió únicamente el listado de limpieza de los archivos propios mediante `DirectoryIterator`, que incluye el archivo oculto. No se silenciaron advertencias ni se cambiaron expectativas. Se añadió después el caso de caché del wrapper, por eso el total final es 135/470. El ejecutor sobrescribió su JSON de resultado al repetir: esta sección conserva la transcripción del resultado y su causa, **no afirma conservar el JSON original**. La salida exacta permanece en el registro de ejecución de la tarea.

El prototipo anterior de 16 comprobaciones en `pruebas/logo_privado/` se conserva íntegro, separado de esta integración. Su contrato experimental de contexto autenticado no es la política final de branding público y no se reutilizó ni se debilitó para conseguir este resultado.

## Pendiente antes de completar la corrección visual

- Confirmar por UI que el entorno tiene una única empresa activa y que su principal es el logo que el usuario desea mostrar. No fijar LIAM en el código.
- Respaldo recuperable de los dos archivos, compatibilidad FPM efectiva y plan de rollback antes de cualquier despliegue autorizado.
- Revisar en navegador el candidato en login, menú y pie, con y sin permisos de administración de logos. No se ha realizado esa revisión del candidato.
- La previsualización de logos no principales en `org_logo/subir_logo` sigue usando su URL estática. No se implementó un endpoint ni un adaptador de preview. Este candidato no soluciona por sí solo esa pantalla ni permite verificar visualmente desde allí el nuevo logo antes de activarlo.
- No borrar el logo anterior hasta resolver y comprobar el nuevo. La recuperación de una eliminación por UI debe evaluarse por separado.

## Aplicación posterior autorizada para revisión

El primer intento de aplicación fue denegado antes de ejecutarse por falta de autorización reconocida; se verificó el helper original intacto, lector ausente, timer inactivo y servicio activo. No hubo despliegue parcial.

Tras una confirmación directa de Martín a las 15:59:20 UTC sobre estos dos archivos, recarga exclusiva de PHP y reversión automática, se efectuó un único reintento. El 18 de noviembre de 2026 a las **16:00:50 UTC** quedaron aplicados y verificados los hashes del candidato, con recarga de `facturacion3-php` y health HTTP 200. El timer de rollback de 45 minutos quedó activo, con vencimiento **16:45:48 UTC**. No se canceló durante esta etapa.

Respaldo y ensayo de restauración verificaron bytes, propietario, grupo y modo del helper original; el lector era un archivo inexistente y el rollback lo retira si conserva el hash candidato. Los scripts no sobrescriben cambios concurrentes ajenos. El rollback automático cubre solo código, no la selección del logo por interfaz. Esa selección y la revisión visual quedan a cargo del flujo UI coordinado. El código no se ha publicado en Git.

Hashes SHA256 aplicados:

- `logo_service.php`: `b2abf7ef0258410171bc4ce9fad159c28ba74a74d65e2d0c950a15b0d3cc9aa3`
- `logo_principal_lector.php`: `61efda420132b72d8311eed5fa427accda69112419e73bcd6d8bb41a909d9bda`
- Helper original respaldado: `a53b4365ad617db9d1a60613bcae42d4165a0262c31a224f3cd5282f48c22521`

## Revisión visual posterior comunicada por el coordinador

Después de aplicar el candidato, el coordinador activó el logo **ID 5** mediante la interfaz autorizada. La UI mostró éxito y la selección persistió al recargar. El login público, encabezado y pie mostraron la imagen LIAM mediante data URI, con dimensiones naturales **1358 × 1158**: PASS visual comunicado por quien operó el navegador. Este agente no repitió esas acciones ni guardó capturas como archivos; las capturas disponibles son las emitidas en línea por el coordinador.

La vista `org_logo/subir_logo` confirma ID 5 PRINCIPAL, pero sus bloques «Logo actual» y miniatura ID 5 todavía utilizan la URL estática: HTTP 404 y `naturalWidth=0`. Es una limitación pendiente del alcance actual, no se declara corregida. El logo anterior ID 4 sigue conservado. No se retiró el timer de rollback durante esta revisión; continúa previsto para 16:45:48 UTC salvo una decisión posterior explícita.

Posible siguiente cambio, todavía sin implementar: reutilizar en la vista administrativa únicamente la imagen principal global ya resuelta, comprobando identidad de empresa, logo y documento contra la fila mostrada. El helper actual entrega solo URL; debe suministrar identidad de la misma selección confiable, no inferirla de GET ni tratar cualquier fila marcada PRINCIPAL como global. Las miniaturas de logos distintos conservarían el comportamiento anterior y no recibirían bytes privados. Este ajuste necesitaría pruebas de render con principal coincidente, fila de otra empresa, no principal, selección ambigua y fallback, además de repetir la batería vigente; no autoriza nuevos endpoints, permisos ni lecturas arbitrarias.

## Cierre posterior del branding y estado de la previsualización

A las **16:05:56 UTC** se canceló la reversión automática, después de verificar la confirmación directa del usuario, hashes candidatos, servicio activo y HTTP 200. El timer quedó inactivo y el respaldo de código se conservó. Las referencias anteriores a las 16:45:48 UTC describen la programación histórica, no un rollback pendiente.

El coordinador informó después que retiró **ID 4** desde la UI autorizada, con éxito y recarga de lista que muestra únicamente **ID 5**. El login continuó mostrando la imagen 1358 × 1158. Este agente no ejecutó esa eliminación ni SQL. El alcance del branding quedó validado; la vista administrativa se aborda en el candidato separado documentado en `LOGO-PREVIEW.md`, todavía sin desplegar al redactar esa nota.
