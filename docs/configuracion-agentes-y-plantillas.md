# Configuración por instalación: agentes y plantillas

El código de Facturación debe ser igual en todas las instalaciones. Los valores locales se definen en `config/generales.php` (ignorado), nunca en el controlador o modelo desplegado.

- `tipo_agente_asesor` es opcional. Si no está declarado, es nulo o no corresponde a un ID entero positivo, se usa `-1` para expresar que no existe un tipo de asesor configurado. El listado conserva el total y los activos, cuenta cero asesores y los formularios no muestran ni requieren el número de asesor. Si se configura un ID positivo, se conserva ese ID, sin suponer el valor de otra empresa.
- Si la relación de agentes está habilitada, conservar las configuraciones de grupos y tipos correspondientes a esa instalación. No se asignan nuevos permisos ni IDs por omisión.
- Si una instalación tiene una plantilla PDF propia, conservarla en `plantillas/local/` y referenciar su ruta mediante `ruta_factura_pdf` en `config/generales.php`. El nombre histórico `plantillas/Plantilla_Factura_ivitec.pdf` también queda ignorado para conservar su configuración actual sin renombrar archivos. Las plantillas comunes ya versionadas siguen en Git.

Antes de desplegar, respaldar configuración y recursos locales, comprobar la ruta efectiva y ejecutar la batería completa. No ejecutar timbrados o escrituras reales como parte de estas pruebas.
