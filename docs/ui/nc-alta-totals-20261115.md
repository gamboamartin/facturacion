# Nota de crédito: Alta y actualización de totales

Incremento exclusivo de presentación probado en Facturación 3 y revisado por Martín. Publicación a main autorizada el 15 de noviembre de 2026, con mensaje de commit claro en español.

## Evidencia previa
NC53 A-000001, partida48: cantidad1 x100, IVA16, retenciones0, total116; sin timbrado, relaciones, correos o notificaciones. Tras alta AJAX, la fila mostraba retenciones undefined y cabecera0; al recargar mostraba importes persistidos correctos. No es fallo de cálculo demostrado.

## Corrección acotada
Alta adopta el diseño existente de factura y conserva campos, IDs, acción y método de formulario. JavaScript NC lee total_retenciones, proyecta totales autoritativos de registro_obj después de regenera_totales y valida respuesta antes de limpiar entrada. No cambia cálculo fiscal, permisos ni backend. IDs duplicados requieren seleccionar cabecera y entrada por su formulario, no selectores globales. Se corrige también un inicio de fila HTML duplicado.

El contrato está sustentado por el JOIN de fc_partida_nc con fc_nota_credito y la relectura de registro_obj en _partida::alta_bd después de regenerar totales. El descuento de cabecera usa fc_nota_credito_total_descuento; no se suman importes en el cliente.

## Caché y orden de carga
El primer ensayo NC54 todavía ejecutó el comportamiento anterior. Lecturas HTTP del JS, con y sin query, devolvieron 200 y SHA256 f8bbb172b6c94b930e6793ea45d680f128c677725350735652a452923bada803, con helper nuevo y sin la clave antigua. La recarga mostró cabecera100/16/0/116 correcta. Esto es compatible con script previo cargado o caché del navegador, no prueba de un cálculo persistido incorrecto.

La vista Modifica NC versiona únicamente la URL existente en js_view mediante hash del archivo. No inserta otro script ni duplica handlers. La vista se incluye antes de emitir js_view en templates/principal.php. El guard acepta la rama no-include igual que el template (false/0), sólo si js_view es string. URL observada en QA final: `./js/fc_nota_credito/modifica.js?v=f8bbb172b6c9`.

## Validación final en FC3
Evidencia comunicada por el coordinador tras su comprobación directa de UI y DB:
- NC54: segunda partida distinta, 10 + IVA1.60. Sin recargar, cabecera subtotal110.00, traslados17.60, retenciones0.00 y total127.60.
- Dos filas, ambas con retenciones0 y sin undefined. Partidas persistidas49/50.
- Sin timbrado; sellados, relaciones, correos y notificaciones:0.
- Factura4772 conserva total116 y saldo116.
- Servicio facturacion3-php activo. Vista Modifica desplegada SHA25673341509f3f45f169c1924457a3403d5e63a6ae7ad01803b036d33444fd072b7.

## Pruebas reproducibles y límites
`tests/regression/nc_totals_projection.js` lee el JS real: 50 casos de proyección (números/cadenas/ceros y rechazo de valores ausentes o inválidos), compilación de sintaxis completa e invariantes de fuente. Ejecutado en V8 sin DOM, red ni DB. Con Node disponible: `node tests/regression/nc_totals_projection.js`. Prueba PHP en memoria del bloque de versionado: 11 casos más idempotencia, URLs con ambas comillas, false/0, rama include y otra vista intactos. Lint PHP y diffcheck correctos.

No se ejecutó la suite DB destructiva. No se emuló móvil ni se certifica aquí adaptación responsive de Modifica NC: alcance de este incremento es diseño de Alta y actualización de totales. No implica aprobación fiscal, timbrado ni prueba de todos los flujos de edición/eliminación.

## Recuperación
Respaldo privado verificado por el coordinador: `/root/fc3-nc-ui-20261115-2ovvVI/original/`. Incluye template Alta, JS Modifica y `views/fc_nota_credito/modifica.php`; se conserva marcador de ausencia de la vista Alta propia.

Rollback de archivos: restaurar esos originales, retirar sólo la vista Alta nueva y recargar exclusivamente facturacion3-php. No restaurar ni modificar datos. El versionado específico está en la vista Modifica respaldada, por lo que también se revierte. No se debe repetir la inserción de partidas para comprobar este rollback.
