# Verificación focal de listas

Pruebas sin red: `python3 pruebas/listas/test_verificar_http.py`.

Verificación real es explícita y de solo lectura. Requiere la aplicación local y una sesión ya autenticada; no envía credenciales ni crea sesiones. Configurar en el entorno privado FACTURACION_QA_URL (por ejemplo http://localhost:81/facturacion/) y FACTURACION_QA_SESSION; no pegar sesiones en reportes ni versionarlas.

Ejecutar `python3 pruebas/listas/verificar_http.py --output /tmp/reporte-listas-nuevo.json`. El archivo debe ser nuevo y bajo /tmp, se crea0600. Solo produce casos/conteos, nunca filas, URL ni tokens. Limita destino a loopback y rechaza redirects/proxies. Paginación/búsqueda deben pasar todas; un caso fallido conserva salida1.

Las doce solicitudes cubren factura, nota de crédito, complemento de pago y fc_layout_nom. No ejecutan timbrado, pagos ni cambios. Ver informe docs/regresion/LISTAS-FC-LOCAL-20261119.md para métricas y límites. La tabla de empleados se verifica aparte en la suite PHPUnit layout_render y los detalles ORM mediante lectura controlada; no inventar rutas fc_nomina/fc_row_layout.
