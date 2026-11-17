# Contraste en modificación de factura

Martín revisó y aprobó el cambio en FC3 el17 de noviembre de2026. Solo CSS `modifica.css` acotado a `#vista_fc_factura_modifica`, identificador del contenedor y versión CSS3 en la vista. No cambia lógica, nota de crédito ni otros formularios. El fondo blanco heredado coincidía con texto blanco de winter-flat.

## Pruebas reproducibles
Ejecutar `python3 pruebas/frontend/modifica_factura/probar_contraste.py` y la batería de `phpunit.xml` bajo aislamiento documentado. Tres pruebas estáticas conservan originales históricos y verifican alcance, contraste declarado y vista sin cambios funcionales. No sustituyen navegador.

## Evidencia y límites
Copia sintética representativa: cliente y método de pago legibles; búsqueda, Escape, Tab, reapertura y cierre exterior conservan valor. Logout correcto. Sin guardar, timbrar ni añadir partidas. No se probó móvil; producto no tenía opciones en fixture. La copia contiene corrección ARIA separada que no se despliega con este cambio.

FC3 recibió solamente CSS y vista con respaldo recuperable ensayado, hashes y propietario/modo verificados. PHP lint, recurso CSS servido exacto, login HTTPS y FPM correctos. Acceso temporal laboratorio cerrado, Nginx original y cinco adaptaciones restaurados, 403 externo confirmado. Facturación real no modificada.

## Recuperación
Restaurar conjuntamente ambos archivos del respaldo verificado, preservando metadatos. En control de versiones revertir este cambio sin tocar trabajo ajeno. No cambiar dependencias ni base de datos.
