# Contraste en modificación de nota de crédito

Revisado y aprobado por Martín en FC3 el 17 de noviembre de 2026. CSS dedicado `css/fc_nota_credito/modifica-contraste.css`, limitado a `#vista_fc_nota_credito_modifica`, enlace e identificador en su vista. No modifica el CSS compartido, los scripts ni lógica de negocio.

Ejecutar `python3 pruebas/frontend/modifica_nota_credito/probar_contraste.py`, las pruebas frontend anteriores y la batería vigente de `phpunit.xml` bajo aislamiento documentado. Vista original en historico; CSS nuevo. Tres pruebas estáticas comprueban alcance, contraste y ausencia de otros cambios de vista; no sustituyen navegador.

Visual sintética: cliente y método legibles; búsqueda, Escape, Tab, reapertura y cierre exterior conservan valores1; logout correcto. Sin guardar ni operaciones reales. Móvil y opciones de producto no probados: fixture de producto vacío. ARIA separada del laboratorio no incluida.

FC3 recibió únicamente vista y CSS con respaldo recuperable ensayado, hashes/metadatos comprobados. CSS HTTP exacto, lint, login HTTPS y FPM correctos. Montaje temporal retirado, Nginx original y cinco adaptaciones restaurados,403 externo confirmado. Facturación real intacta.

Recuperación: restaurar vista desde respaldo y retirar CSS nuevo al respaldo recuperable. En Git revertir únicamente este cambio. Sin dependencias ni base de datos.
