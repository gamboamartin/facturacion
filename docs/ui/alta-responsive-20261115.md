# Alta de factura: presentación responsive

## Alcance
Cambio exclusivamente visual de Alta en Facturación 3. No modifica modelos, cálculos fiscales, rutas POST ni JavaScript de negocio. Sin push a main hasta validación expresa de Martín.

## Cambios
- Cabecera Nueva factura y tarjetas Emisor y cliente, Datos del comprobante, Pago y detalles.
- Paleta morada existente, etiquetas encima de campos, dos columnas de escritorio y una a menos de 768 px.
- Selectores de ancho completo, controles de al menos 44 px, foco visible y modal adaptado a móvil.
- Corrección de comilla HTML faltante del modal; subtítulo redundante oculto sólo dentro de Alta.
- Los bloques PHP, campos, orden, condicionales, método y action del formulario permanecen idénticos a la base.

## Verificación
- PHP lint de las dos vistas: PASS local y staging servidor.
- Comparación ordenada de todos los bloques PHP con HEAD previo: idénticos.
- git diff --check: PASS.
- Regresión CP autocontenida: 12 casos PASS; sin DB ni bootstrap.
- CSS sin recursos externos, sin JavaScript nuevo.
- Desplegados únicamente dos vistas y una hoja CSS; hashes verificados. Servicio dedicado activo, página y CSS HTTPS 200.
- QA real escritorio: vista nueva visible y selector Cliente abre/cierra sin desbordamiento horizontal; no se guardó factura.
- QA real dependencias: selección Servicio correcta; seleccionar MXN actualizó tipo de cambio a MXN 1. Plantilla permaneció deshabilitada; no se confirmó si hay plantillas disponibles. Sin guardado.
- Móvil: reglas revisadas estáticamente; no se dispone todavía de verificación en viewport móvil real. No presentar como prueba móvil completa.
- Guardado y timbrado no ejecutados. Suite de integración DB no ejecutada: contiene operaciones destructivas ajenas a este cambio.

## Despliegue y reversión
Activado en FC3 el 2026-11-15 17:09:20 UTC. Respaldo privado de las dos vistas anteriores verificado antes de sustituirlas; se registró que la hoja CSS nueva no existía. Vistas reemplazadas mediante rename, permisos/propietario conservados, reload sólo del servicio dedicado FC3 por OPcache.
Para revertir: restaurar ambas vistas desde el respaldo verificado, retirar la hoja CSS agregada, recargar únicamente el servicio FC3 y verificar hashes/Alta. No se necesita revertir base de datos.

## Pendiente
Revisión del usuario en Facturación 3, especialmente desde teléfono. No push automático a main. Las capturas con identificadores visibles tienen autorización de publicación pendiente y se gestionan por separado.
