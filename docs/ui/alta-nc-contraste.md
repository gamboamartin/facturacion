# Contraste en alta de nota de crédito

Revisado y aprobado por Martín en FC3 el 17 de noviembre de 2026. CSS dedicado `css/fc_nota_credito/alta-contraste.css`, limitado a `#form_fc_nota_credito_alta`, enlazado únicamente en su vista. No modifica el CSS compartido con factura y complemento ni lógica.

Ejecutar `python3 pruebas/frontend/alta_nota_credito/probar_contraste.py`, las pruebas frontend anteriores y la batería vigente de `phpunit.xml` bajo aislamiento documentado. La vista original se conserva en historico; el CSS es nuevo. Tres pruebas estáticas comprueban alcance, contraste y vista sin otros cambios; no sustituyen navegador.

Visual sintética: cliente y método legibles; búsqueda, Escape preserva vacío; selección método, Tab, reapertura y cierre exterior preservan valor1; logout correcto. Sin Alta ni operaciones reales; móvil no probado. ARIA separada existente en laboratorio no incluida.

FC3 recibió vista y CSS con respaldo ensayado, metadatos y hashes comprobados. Recurso HTTP exacto, lint, login HTTPS y FPM correctos. Retirada temporal del laboratorio, configuración original restaurada y403 externo confirmado. Facturación real intacta.

Recuperación: restaurar vista del respaldo y retirar CSS nuevo al respaldo recuperable, sin eliminar definitivamente ni tocar archivos compartidos. En Git revertir únicamente este cambio.
