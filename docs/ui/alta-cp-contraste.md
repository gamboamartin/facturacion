# Contraste en alta de complemento de pago

Revisado y aprobado por Martín en FC3 el 17 de noviembre de 2026. CSS exclusivo `css/fc_complemento_pago/alta-contraste.css` limitado a `#form_fc_complemento_pago_alta`, enlazado en su vista. No cambia CSS compartidos, lógica financiera ni dependencias.

Ejecutar `python3 pruebas/frontend/alta_complemento_pago/probar_contraste.py`, pruebas frontend anteriores y batería vigente `phpunit.xml` bajo aislamiento documentado. Histórico original conservado; CSS nuevo. Tres pruebas estáticas verifican alcance, contraste y solo enlace de vista. No sustituyen navegador.

Visual sintética: cliente y tipo cambio legibles; búsqueda, Escape, Tab, reapertura y cierre exterior conservan valores vacío/1; logout. Sin Alta, pagos, timbrados ni registros. Otros catálogos fiscales carecen de opciones en fixture: no se afirma su selección. No móvil. ARIA independiente del laboratorio no incluida.

FC3 recibió solo vista/CSS con respaldo y recuperación ensayados, metadatos/hashes, lint, recurso HTTP exacto, login HTTPS y FPM verificados. Montaje temporal cerrado, Nginx original y cinco adaptaciones restaurados;403 externo confirmado. Facturación real intacta.

Recuperación: restaurar vista y retirar CSS nuevo al respaldo recuperable. No cambiar reglas de pago ni base.
