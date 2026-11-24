# Complemento desde factura: laboratorio

El ejecutor `ejecutar.py` requiere los recursos `QA_CSD_*` y el modo
`--complemento-desde-factura` del motor aislado existente. Debe ejecutarse desde
una copia aislada del repositorio. Usa MariaDB efímera en tmpfs, sin red, con
fuentes y dependencias en modo lectura y configuración ficticia.

Las pruebas del servicio utilizarán tablas sintéticas y un callback de creación
que solo escribe estructura ficticia en la misma conexión PDO, sin commit propio.
Esto permite comprobar permisos, autoridad de datos, idempotencia y rollback de
la orquestación con SQL real. El callback no ejecuta modelos heredados, genera
XML, lee certificados ni llama un PAC.

El GET del servicio se verifica con SQL de solo lectura. El GET HTTP completo
hereda constructores de la aplicación que pueden crear tipos de cambio del día;
esta suite no afirma que ese recorrido heredado completo tenga cero escrituras.
El recorrido HTTP completo y los modelos fiscales completos requieren pruebas
específicas, sin PAC real. La consulta SQL de ACL real se comprueba en esta suite
con sesión sintética, cuatro permisos y revocación entre dos conexiones, incluso
cuando la transacción del lector ya tenía un snapshot previo.

La suite acredita también los helpers reales de `fc_docto_relacionado` mediante
Reflection sin ejecutar su constructor: el bloqueo usa lecturas actuales aun con
un snapshot previo; dos conexiones respetan el bloqueo de fila; el `alta_bd` real
revierte su transacción propia o su savepoint cuando una contraparte de pago
ficticia falla, preservando cambios anteriores de la transacción del llamador.
No se ejecutan cálculos de impuestos en esa interrupción controlada.

`esquema_referencia.json` recoge exclusivamente columnas, relaciones y hashes de
DDL de un export de metadatos reales de desarrollo, sin filas. El fixture mínimo
contrasta sus columnas con esa referencia. La relación de notas sigue
`fc_nc_rel.fc_relacion_nc_id → fc_relacion_nc.fc_nota_credito_id`, conservando los
mismos casos de saldo; no se inventan claves directas ausentes del esquema real.
La referencia no reproduce todos los defaults, restricciones o campos obligatorios
del ORM completo: la prueba independiente de cadena ORM debe conservar ese alcance.

Las unitarias están en `pruebas/regresion/complemento_desde_factura/`; su registro
local enumera 33 identidades para integrar en el registro central. Cubren validación
y autorización sin SQL, CSRF/403 del trait real y encadenamiento de los tres modelos
dobles mediante el método real `crearModelos`. No usan proveedores de datos que
expandan identidades fuera del registro.

Registro SQL final: 24 identidades. Los importes de cabecera del CP se comprueban
en cero de forma explícita; el importe recibido permanece en Pago/Docto.

La suite unitaria de `_saldos_fc` agrega 10 identidades: deriva el saldo de CP/NC,
respeta redondeo y `aplica_saldo`, escribe únicamente cuatro campos con PDO y
prohíbe entrar a `fc_factura.modifica_bd` (sin comisiones ni efectos de cabecera).
Comprueba fallos de lectura/prepare/execute/excepción y que rowCount=0 no sea
confundido con error cuando los valores ya coinciden.

La frontera POST conserva el contexto de errores anterior y descarta los errores
heredados de la operación: se comprueban excepción y exit, con colección previa,
valor null, clave ausente y bandera anterior true. La salida sintética privada no
aparece en la respuesta ni queda guardada para una vista posterior.
