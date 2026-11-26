# Descarga separada de XML y PDF

Las tres vistas reutilizan un fragmento y un ejecutor JavaScript. El navegador espera el cuerpo completo del XML y comprueba que sea un CFDI parseable antes de solicitar el PDF; un error en un archivo se informa y permite continuar con el otro. Las descargas conservan nombres de `Content-Disposition` y los bytes recibidos. Se fuerza la descarga del PDF incluso si el servidor lo responde como `inline`.

La pantalla queda abierta, conserva enlaces individuales y distingue entre enviar un archivo al navegador y confirmar que el usuario lo haya guardado. Las restricciones del navegador para múltiples descargas requieren permitirlas o usar dichos enlaces; el sistema no puede eludir esa preferencia.

```bash
python3 pruebas/frontend/descargas_separadas/probar_descargas.py
```

La prueba utiliza PHP con datos sintéticos y Chromium real con todas las solicitudes interceptadas. Comprueba orden de solicitudes y eventos `download`, nombres y contenido, XML de más de seis segundos, PDF `inline`, nombres UTF-8, fallos HTTP, HTML de inicio de sesión con MIME correcto o incorrecto, XML/PDF malformados, URL alterada y redirecciones, así como enlaces manuales tras simular bloqueo de descargas automáticas. No carga configuración privada ni abre la aplicación, bases, documentos de producción o PAC.

La suite histórica `pruebas/descargas_complemento/probar.php` conserva sus casos de metadata y permisos. Su aserción de presentación deja de exigir dos iframes paralelos, pues el usuario solicitó expresamente orden XML → PDF y la implementación anterior no lo garantizaba; ahora exige ambos enlaces y el ejecutor común, mientras esta prueba de navegador acredita la secuencia real.

El despliegue debe eliminar explícitamente `js/fc_factura/descargar_por_separado.js` y conservar el nuevo `.js.php`. El cargador nativo prioriza el `.js` y no agrega versión a su URL: dejarlo físicamente en el servidor permitiría reutilizar la copia anterior que cerraba la ventana a los seis segundos. El nuevo include PHP no solicita esa URL obsoleta; el ejecutor compartido de la vista sí usa la fecha del archivo como versión.
