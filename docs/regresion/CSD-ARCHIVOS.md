# CSD: compensación de archivos en copia

Estado: candidato no publicado ni desplegado. No se usaron certificados reales,
PAC, timbrados, pagos ni cancelaciones. No se modificó configuración activa.

## Alcance y dependencias

- Facturación: contexto por solicitud, journal de archivos, subclase documental,
  integración opt-in en `_cert` y control explícito de errores de `alta_bd`.
- Documento: hook protegido de escritura de alta. Sin subclase conserva la
  llamada histórica a `files::guarda_archivo_fisico`. La copia parte de master
  `41bb6d435198e427660af9cd880210caf4c97ede`, resuelto también por v24.3.0.
- Plugins: conversión OpenSSL PHP preparada por separado. No se modificó el
  escritor documental global para compensación CSD.

La dependencia Documento anterior carece del hook y se rechaza antes de abrir
la transacción. No basta copiar Facturación sin publicar y fijar dependencias
compatibles. No se ha actualizado ningún lock activo.

## Propiedad y recuperación

Los documentos nuevos se reservan de forma exclusiva, con modo 0600 y raíces
explícitas. No se sobrescriben rutas preexistentes. Se rechazan enlaces y rutas
fuera de las raíces; el retiro comprueba dispositivo, inode, enlaces, propietario,
tamaño y hash. Un archivo sustituido o modificado se conserva y se informa.

Los PEM temporales se generan en directorios exclusivos 0700. Tras commit se
limpian sólo temporales; los documentos confirmados dejan de ser elegibles para
compensación. Antes de commit se compensan archivos únicamente después de
rollback SQL confirmado. Si commit o rollback es incierto, no se borran.

Un fallo de retiro genera un recibo privado 0600 en directorio 0700 con rutas e
identidades exactas, sin contenido ni contraseñas. La respuesta pública contiene
sólo ID y código. El recibo marca el estado SQL y **no autoriza eliminación
automática**. Si no puede guardarse, se devuelve `guardado=false`; no se declara
recuperación acreditada.

Límite: se requieren directorios controlados por el servicio. Las comprobaciones
no equivalen a protección contra un administrador malicioso que sustituya rutas
concurrentemente. Tampoco se acredita compensación ante `die`, señal o fallo
fatal en colaboradores profundos; no hay un barrido automático del almacén.

## Precondición concreta de FC3

La inspección de sólo lectura encontró `archivos/doc_documento` con propietario
y grupo `facturacion3`, modo 0750, pero **no existe `archivos/temporales`**.
El candidato falla antes de comenzar transacción si faltan las raíces. No las
crea implícitamente. Una eventual preparación del directorio temporal privado
0700, del mismo propietario, requiere el flujo de autorización/despliegue
correspondiente; no se realizó aquí.

## Pruebas ejecutables y límites

`pruebas/csd_archivos/registro.json` contiene 46 comprobaciones:
13 de journal, 14 de hook/contexto, 1 de dependencia antigua 7 del consumidor `_cert` candidato y 10 escenarios del
controlador. El controlador, journal, hook y conversor son reales; se simulan
PDO, modelos y persistencia. Los archivos son ficticios reales en disco.
Los escenarios cubren éxito, KEY/CER fallidos, commit que lanza o devuelve false,
rollback incierto, error de navegación después de commit, cleanup fallido e
inicio de acción fallido. La limpieza del arnés se verifica.

Esto **no sustituye** las 13 pruebas ORM documentales, una alta CSD integral con
base efímera ni una solicitud FPM. La base vigente de Facturación (155 pruebas,
539 aserciones) y 21 frontend se conservan separadas.

Ejecutor agregado: `python3 pruebas/ejecutar_completa.py`. La suite focal exige
rutas explícitas a fuentes de prueba mediante `FUENTE_DOCUMENTO`,
`FUENTE_DOCUMENTO_ANTIGUO`, `AUTOLOAD_PRUEBAS`, `FUENTE_PLUGIN_FILES`,
`FUENTE_ERRORES` y `FUENTE_SSL`. Deben apuntar exclusivamente a copias acreditadas;
no a configuración o secretos activos. El ejecutor usa PHP sin ini, deshabilita
funciones de red/procesos y bloquea ante warnings, fallos u omisiones focales.

Resultados de esta preparación: 46 focales PASS y base 155/539 + 21 PASS.
Las 13 pruebas ORM documentales aprobaron en base efímera con el hook candidato
y plugins baseline v6.22.10 (84 aserciones exitosas). No acreditan el conversor
candidato dentro de una alta CSD integral, que permanece pendiente;
no corresponde publicar ni desplegar por estos resultados parciales.


## Documental con base real efímera

Se ejecutaron los 13 casos originales con DDL oficial sintético (19 tablas),
cuenta exclusiva de la base de ensayo, red aislada, fixtures versionados copiados
a tmpfs y `aplica_seguridad=true`. No se desactivaron ACL para hacer pasar casos.
Se modificaron únicamente las dos posiciones de columnas cuya actualización
aprobó Martín: el instalador administrativo vigente crea código antes de
descripción. No se cambió el esquema para reproducir una expectativa histórica.
El original íntegro permanece recuperable.

Resultado: 13/13, 84 aserciones exitosas y cero incidencias PHPUnit/JUnit; cierre
SQL con identidad verificada y motor terminado. Avisos WSREP propios del
namespace sin red se conservaron y no se confundieron con advertencias de las
pruebas. La observación de procesos está limitada por entradas `/proc` ajenas no
legibles; dentro del namespace no se observaron procesos restantes.

El escenario focal `error_par` valida propagación del rechazo criptográfico real a través de un colaborador doble y rollback del controlador real. No acredita que `fc_csd::genera_pems` real invoque esa validación; ese requisito se mantiene en el ensayo ORM integral separado.

Integración ORM real en MariaDB efímera: tres casos PASS tras corrección autorizada de pareja CER/KEY. Primer fallo (par distinto aceptado) preservado; rechazos posteriores no generan PEM ni modifican originales. Rollback e inventario físico verificados. No equivale a controlador HTTP/FPM ni commit SQL. Véase `pruebas/csd_integral/README.md`. La comprobación focal adicional de Plugins antiguo rechaza antes de acceder a datos.

Batería vigente final: agregado de 10 pasos PASS (155 pruebas/539 aserciones + 46 focales + 3 integrales ORM obligatorias + 21 frontend). El gate de integración bloquea ante recursos ausentes, deriva hashes efectivos y verifica cierre. No se publicó ni desplegó esta reparación.

## Revisión UI FC3 del 18 de noviembre de 2026 (comunicada por coordinador)

Un único clic Alta aproximadamente a las 19:46 UTC redirigió mediante GET a
Modificar, registro CSD 20, con mensaje «Muy bien» y objetos creados. Una recarga
GET conservó serie QA1811, número 90000000000000000001 y sucursal
QA1811-SIN-VALIDEZ-org_sucursal. No hubo pantalla blanca. El emisor QA estaba
inactivo y su sucursal activa; el branding LIAM permaneció intacto. Sólo se usaron
CER/KEY ficticios, sin PAC ni operaciones fiscales.

La tabla Archivos se mostró vacía: queda pendiente su diagnóstico y no se marca
PASS de esa vista. La captura existe únicamente inline en la sesión CUA; el intento
de guardarla en /tmp obtuvo EROFS. No se afirma entrega de archivo de captura.

La persistencia inicial acreditó 22 filas y cuatro archivos documentales propios;
los hashes de CER/KEY coincidieron con el par ficticio y sus permisos fueron 0600.
Existe respaldo privado con grafo tipado y copias verificadas; esa verificación de
copias no equivale a restauración completa de la base. Limpieza/recuperación quedan
registradas separadamente cuando terminen. El timer de reversión sigue activo.

### Cierre posterior de la prueba UI

A las 19:52:21 UTC se retiraron los 22 registros ficticios y cuatro archivos propios,
tras confirmación inmediata del usuario que aceptó los límites de recuperación
informados. Se verificó ausencia del grafo y archivos, 42 referencias revisadas y
conservación de datos compartidos/empresas activas. El respaldo privado permanece;
no se afirma restauración funcional demostrada.

Verificación independiente posterior a las 19:53:25 UTC: nueve hashes runtime
coincidentes, servicio PHP exclusivo activo, temporales vacíos modo 0700 y timer
activo. Reversión conservadora prevista para 20:27:27 UTC; no fue cancelada durante
este cierre. La tabla Archivos vacía se contrastó con código base/candidato/activo:
Modifica no inicializa ni carga la colección, mientras subir_cer sí lo hace. Es una
limitación preexistente de presentación, no ausencia del CER creado ni regresión
del candidato. No se reparó ni se declaró PASS de esa tabla.

## Cierre de publicación y preservación del fixture oficial

Las dependencias publicadas son Plugins v6.22.11 (`491c8067ff20cd37ac452a00e7c073ef7c58da8e`) y Documento v24.3.1 (`c44f9f472a2027c4d1151e1013c235aebec89fa7`). El lock modifica sólo esos dos paquetes; los otros 101 permanecen idénticos. La instalación limpia privada desde Satis y la comprobación de plataforma fueron aprobadas, sin scripts ni plugins de Composer ni configuración activa.

El fixture `pruebas/csd_integral/fpm/views.php` conserva exactamente el archivo oficial `config/example.views.php`, SHA-256 `30278c62660152d9807e7d62b93dd46a1c1a436e6e2dc21f4db4d89dbf72681c`. Sus incidencias de whitespace preexistentes (línea 35 y línea vacía final 54) se excluyen únicamente del control adicional `git diff --check`, con aprobación de coordinación. El resto del diff se comprueba íntegro; no se desactivan pruebas ni se cambian expectativas o bytes del fixture.

La primera ejecución del gate en la copia de publicación se bloqueó por falta de `vendor/autoload.php`, antes de probar el rechazo de dependencia antigua. Se conservó la evidencia y se materializó el vendor oficial instalado del lock, sin credenciales ni datos de aplicación. Debe repetirse la batería completa antes del commit; el resultado anterior no constituye aprobación.
