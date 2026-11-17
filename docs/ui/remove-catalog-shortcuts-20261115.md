# Retirar accesos inferiores de catálogos

Segundo incremento separado de la versión visual aprobada y publicada en main62979d8.
Se retiran exclusivamente los bloques de render buttons_parents_alta de Alta y Modifica: Alta Sucursal, Régimen Fiscal y demás altas de catálogo inferiores. No se eliminan permisos, rutas, catálogos ni datos; tampoco se alteran botones propios de factura, submit, partidas o scripts.

Revisión independiente del diff, lint local/remoto y diffcheck: PASS. Respaldo exacto de las dos vistas verificado; deploy sólo FC3 el 2026-11-15T19:01:40Z, hashes coincidentes y servicio dedicado activo. Rollback: restaurar ambas vistas desde respaldo y reload exclusivo FC3.
Prueba navegador pendiente de registrar. No push de este incremento hasta revisión y confirmación expresa de Martín.
