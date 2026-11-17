# Contraste de opciones en alta de factura

Corrección aprobada por Martín en Facturación 3 el 17 de noviembre de 2026.

## Alcance
Solo `css/fc_factura/alta.css` y versión de ese recurso en `views/fc_factura/alta.php`. Los seis bloques nuevos se limitan a `#form_fc_factura_alta`: no modifican nota de crédito, complemento de pago ni lógica de negocio. El fondo heredado blanco y texto blanco de winter-flat causaban las opciones invisibles.

## Reproducción
`python3 pruebas/frontend/alta_factura/probar_contraste.py` ejecuta tres pruebas estáticas: alcance, contraste declarado y cambio exclusivo de versión en la vista. Los originales se conservan en `historico/`. No sustituyen la comprobación de estilos computados en navegador. Ejecutar también la batería unitaria definida por `phpunit.xml`, con el aislamiento documentado en pruebas/regresion.

## Verificación
En copia sintética representativa: reproducción anterior, cliente y método de pago con opciones legibles; búsqueda, Escape, Tab, selección, reapertura y cierre exterior. Sin guardar formularios ni operaciones reales. Contrastes declarados: normal 14.55:1; foco 12.34:1; activo 6.24:1; deshabilitado 5.54:1. No se probó móvil ni el resto de vistas. La copia incluye una reparación ARIA independiente que NO forma parte de este cambio ni del despliegue FC3.

Despliegue focal FC3 con originales verificados, copia recuperable y ensayo de restauración; PHP lint, recurso HTTP exacto, login HTTPS y servicio FPM correctos. El acceso temporal del laboratorio se retiró, se restauró Nginx y se confirmó 403 externo. Facturación real no fue modificada.

## Recuperación
Restaurar conjuntamente CSS y vista desde el respaldo previo verificado del despliegue. Para control de versiones, revertir este cambio conservando otros trabajos. No instalar dependencias ni cambiar base de datos.
