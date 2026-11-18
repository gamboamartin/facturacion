# FPM local: validación ejecutada y plan de traslado pendiente

## Diseño inicial histórico (conservado; estado actualizado abajo)

Las secciones iniciales describen la preparación previa, no el estado vigente.
La corrida 07 y el agregado posterior ya acreditaron el éxito local.

### Capacidades verificadas durante la preparación

Desarrollo dispone de `/usr/sbin/php-fpm8.3` (8.3.17, SAPI fpm-fcgi) y `/usr/bin/cgi-fcgi`. No se inició FPM ni se recargó un servicio. La biblioteca OpenSSL del SAPI deberá acreditarse con `sonda.php` después del arranque autorizado; no inferir equivalencia con FC3 PHP 8.3.36/OpenSSL 3.0.2 a partir del CLI local.

## Aislamiento propuesto

Crear un launcher nuevo, sin alterar `infra/ejecutar.py` vigente, reutilizando sus verificaciones, motor efímero y límites. Añadir sólo tmpfs privado `/run/php-fpm` (4 MiB) y directorios 0700 `/tmp/fpm-qa/{uploads,sessions}`. Config/ini se montan readonly desde esta carpeta. Se conservan netns sin rutas, capacidades eliminadas, mounts del host readonly, dos CPU, AS 2 GiB y límites globales vigentes. FPM master y worker se ejecutan con el usuario no privilegiado del namespace. No nginx, puerto TCP, servicio host, cambios de firewall/AppArmor o credenciales reales.

Arranque propuesto dentro del namespace:

```sh
PHP_INI_SCAN_DIR='' /usr/sbin/php-fpm8.3 --nodaemonize --fpm-config /qa-fpm/php-fpm.conf --php-ini /qa-fpm/php.ini
```

Usar entorno mínimo explícito, incluyendo `QA_HOST_NET_NS`, `OPENSSL_CONF=/qa/openssl.cnf` y `MALLOC_ARENA_MAX=2`; nunca heredar env de credenciales. Un worker estático, máximo ocho peticiones, request timeout 30 s. El socket Unix es 0600 dentro de directorio 0700. No existe listener de red. PHP deshabilita funciones de comandos y conexiones; netns y ausencia de rutas son una barrera adicional, no sólo esas funciones. No aumentar límites para forzar PASS.

## Peticiones y alcance

1. Acreditar PID/ejecutable/socket/namespace y ejecutar `sonda.php` con `cliente_fcgi.py` para verificar FPM y versión real OpenSSL.
2. Preparar entrypoint `alta_controlador.php` exclusivamente del arnés, aún pendiente. Debe usar autoload real sin el bootstrap CLI que borra POST/FILES. Cargar sesión ficticia del servidor y parámetros sintéticos revisados, nunca credenciales ni sesión de usuario real. Invocar controlador real y ORM real. No sustituir lógica de negocio con dobles.
3. Generar CER/KEY y multipart dentro de tmpfs, sin contraseña en argumentos ni logs. El cliente sólo admite los dos scripts enumerados y socket Unix exacto; body sólo bajo `/tmp/fpm-qa`. Las cargas deben entrar por PHP como `$_FILES` reales, no forjar su persistencia.
4. Verificar éxito/error, encabezados/respuesta, DB/archivos y recuperación. Definir expectativas antes de ejecutar. Un resultado de CLI o sólo sonda no constituye alta HTTP aprobada.

Este ensayo es SAPI/FastCGI local. No equivale a UI, login/dispatcher de producción, navegador, despliegue FC3 ni validación SAT/PAC. Ningún timbrado, pago o cancelación.

## Retirada

Antes de SIGQUIT al master propio, verificar PID file, starttime, hash ejecutable y namespace. Esperar salida del master y sus workers; comprobar socket y procesos de ese namespace. No asumir que desaparecer el padre prueba el cierre de todo worker. Si la política impide la señal, no cambiar seguridad ni intentar otras identidades: informar BLOQUEADO y usar la terminación del namespace ya existente como barrera final, declarando el límite de evidencia. Cerrar MariaDB por el procedimiento SQL de identidad verificada y wait. Eliminar el namespace destruye tmpfs, uploads y certificados ficticios. No borrar archivos del host.

## Pendientes antes de ejecutar

Revisión del launcher nuevo y política de señales; entrypoint controlador sin bypass de reglas de negocio; fixture autorizado de sesión/datos; parseo estricto de respuesta FastCGI; detección de errores PHP/FPM y comprobación de limpieza. Los archivos presentes son preparación, no un ensayo ya ejecutado.

## Evidencia local posterior

Sonda 01 ejecutada el 18-11-2026: FPM 8.3.17/OpenSSL 3.0.13 por respuesta FastCGI, sin comandos externos ni URL remotas. SIGQUIT al master propio tras identidad y starttime fue permitido: master exit 0, workers ausentes, socket retirado. MariaDB shutdown con identidad y wait 0. Avisos NOTICE sobre user/group ignorados al ejecutarse sin root conservados; no se cambió identidad ni servicio host.

Primera alta FastCGI: cargas multipart SAPI verdaderas, pero constructor real no retornó; no se creó ningún CSD/documento. Resultado bloqueado. Se conserva evidencia incluso aunque el launcher inicial mantuvo por error el estado de la sonda; ese catch quedó corregido para devolver BLOQUEADO y exit distinto de cero. Además hubo aviso de edición concurrente del entrypoint: esa corrida no se usa como validación del candidato. No hubo repetición automática de POST. Pendiente diagnóstico saneado de la salida interna del constructor.

## Resultado y gate obligatorio (18 de noviembre de 2026)

La corrida 07 pasó con PHP 8.3.17 y OpenSSL 3.0.13 locales: constructor real,
`alta_bd(false, false)` y uploads reales de FastCGI. Se confirmaron un CSD,
nueve etapas, un CER, un KEY, dos PEM y cuatro documentos ficticios. Se cerraron
FPM y MariaDB, se retiró el socket y no quedaron procesos visibles del namespace.
Las corridas anteriores permanecen conservadas como evidencia privada: no se
reescribieron sus resultados. Los bloqueos 04–06 correspondían a configuración
sintética incompleta, no a expectativas relajadas.

`python3 pruebas/csd_integral/ejecutar_fpm.py` es ahora un gate obligatorio separado,
incluido en `pruebas/ejecutar_completa.py`. Usa las mismas cinco variables
`QA_CSD_ENGINE_ROOT`, `QA_CSD_DEPS`, `QA_CSD_DOCUMENTO`, `QA_CSD_PLUGINS` y
`QA_CSD_DDL` documentadas para la integración ORM. Requiere el motor, dependencias,
DDL sanitizado, PHP-FPM y aislamiento descritos arriba. Un recurso ausente, timeout,
mutación de fuentes, alta incompleta o cierre no acreditado bloquea el resultado.
Cada ejecución conserva un directorio nuevo de evidencia; no reutiliza un PASS.

Este caso comprueba sólo el éxito local. Los rechazos de contraseña y pareja y
la compensación se acreditan mediante las suites ORM/focales separadas. No prueba
login, dispatcher, navegador, redirección ni la versión FPM de FC3. No contiene
credenciales ni certificados reales ni realiza llamadas PAC.

## Siguiente paso FC3, todavía no ejecutado

1. Verificar nuevamente referencias remotas y candidatos exactos de los tres
   repositorios. Las versiones propuestas Plugins v6.22.11 y Documento v24.3.1
   todavía no están publicadas; el lock actual no instala estos candidatos.
2. Preparar un manifiesto cerrado de archivos de código/arnés para un laboratorio
   privado en FC3 y solicitar autorización específica de transferencia/ejecución.
   No reutilizar la autorización histórica de cuatro archivos para otro lote.
3. Validar allí la versión efectiva PHP/OpenSSL con certificados ficticios,
   almacén y base efímeros, sin red PAC ni configuración real. FC3 no tiene el
   mismo aislamiento bwrap local: requiere un plan equivalente acreditado antes
   de ejecutar, no una copia ciega del launcher.
4. Para revisar por UI, acordar montaje y acceso concreto con sesión sintética,
   respaldo, limpieza y cierre. El éxito FastCGI local no sustituye esa revisión.
5. Sólo después de las pruebas y revisión acordadas, coordinar publicación de
   dependencias, catálogo Satis y lock focal reproducible; repetir toda la batería
   inmediatamente antes de cada commit y antes del despliegue. No anticipar
   etiquetas ni referencias inexistentes en un lock presentado como instalable.

Precondición activa conocida: el almacén documental de FC3 existe, pero su raíz
privada de temporales CSD no existe. Su preparación futura con propietario del
pool exclusivo y modo 0700 requiere revisión/autorización aplicable; no se creó.
No se ha cambiado FC3, su seguridad, certificados, PAC ni datos durante esta fase.

La repetición posterior autorizada del agregado terminó PASS el 18 de noviembre
 de 2026 a las 18:17 UTC: 11 pasos, 155 pruebas/539 aserciones de base, 46 casos
focales, 3 ORM, 1 FPM y 21 comprobaciones frontend. El resultado ejecutable está en
`pruebas/resultados/completa.json`; el detalle FPM en `csd_fpm_local.json` del mismo
directorio. Esta repetición no publicó ni desplegó los candidatos.

## Propuesta concreta de aislamiento FC3 sin bwrap (no ejecutada)

Capacidades acreditadas previamente: unshare, mount, Python, cgi-fcgi, PHP-FPM y
MySQL 8.0.46. No se acreditó bwrap ni MariaDB 10.6 en ese servidor. Por tanto el
launcher local no se puede ejecutar allí sin adaptación. No se instalará software
ni se cambiarán servicios, firewall, permisos globales o sysctl para hacerlo pasar.

El adaptador propuesto usará unshare de mount/PID/IPC/UTS/red, propagación privada,
rootfs temporal y chroot, con mounts selectivos de binarios/bibliotecas sólo lectura;
no montará /root, sockets del host, configuración activa, sesiones ni almacenes
reales. Dentro: datadir MySQL nuevo en tmpfs, --no-defaults, --initialize-insecure
sólo sobre datadir vacío privado, --skip-networking y socket propio. La cuenta QA
se limita al esquema efímero. FPM exclusivo de ensayo sin demonizar, socket 0600,
usuario sin privilegios, capacidades descartadas, entorno mínimo y límites iguales
al ensayo local. Antes del alta deben acreditarse namespaces distintos, ausencia
de rutas/listeners TCP, hashes de ejecutables y fuentes, configuración sintética,
FK activas y ningún mount host escribible. No se reutiliza el MySQL de FC3.

Esto requiere preparar y revisar el adaptador y confirmar capacidades concretas
(chroot, descarte de privilegios/capacidades, bibliotecas y arranque MySQL efímero)
antes de solicitar ejecución. MySQL 8 frente a MariaDB 10.6 constituye una diferencia
real: el DDL y fixtures deberán pasar sin modificar expectativas unilateralmente.
Si namespaces/privilegios necesarios no están disponibles, detener; no relajar
seguridad. No hay aún un comando de despliegue probado para esta propuesta.

Retirada: cerrar sólo master FPM identificado y sus workers, shutdown del MySQL
propio identificado, verificar procesos y sockets ausentes y terminar namespace.
La desaparición de tmpfs retira certificados y datos ficticios; conservar sólo
resultados sanitizados fuera de él. Nunca señalar servicios activos ni borrar
rutas compartidas. La prueba por UI necesitará después una ruta de acceso concreta
aprobada: esta propuesta privada por socket no proporciona navegador remoto.

### Candidatos antes de publicación

Para respetar revisión FC3 antes de push, usar en la copia privada un overlay de
los nueve archivos runtime candidatos sobre una base de código y dependencias
acreditada por hashes. No alterar vendor/lock activos. Registrar versiones base y
hash de cada overlay; esto es una validación de candidatos, no instalación de una
versión publicada ni un lock entregable. El lote propuesto contiene sólo código,
arnés/configuración enteramente sintética y DDL sanitizado. La base de código y
vendor se prepararían mediante lista permitida y copia local revisada, excluyendo
configuración, sesiones, certificados y datos. Cualquier discrepancia de base
bloquea antes de probar, no se resuelve sobre el sitio activo.

Tras revisión y autorización, cada repositorio necesita su batería vigente antes
de commit/publicación; después actualizar Satis y resolver lock definitivo focal,
repetir pruebas y comparar con los candidatos revisados antes del despliegue final.
No publicar dependencias anticipadamente para hacer posible esta prueba privada.
