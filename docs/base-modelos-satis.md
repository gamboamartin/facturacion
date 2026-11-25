# Base_modelos v5.1.0 mediante Satis

El paquete mantiene el tag v5.1.0, commit `23e0c34a27f5afc8d14b1973654631b91004ec3e`, sus diez archivos y los contratos de la clase que hereda de validacion. La raíz declara Satis canonical con only para base_modelos; el lock cambia exclusivamente su distribución a HTTPS con checksum, sin fallback source ni notificación Packagist. No cambian las versiones ni referencias de los demás paquetes.

La suite `python3 pruebas/base_modelos_satis/ejecutar.py` valida contrato y consumidores reales con datos sintéticos, sin configuración privada ni conexión de producción. Se integra como puerta obligatoria en `python3 pruebas/ejecutar_completa.py`, que debe finalizar con PASS_BATERIAS_REGISTRADAS en laboratorio aislado, incluyendo integración ORM efímera y PDF. No se timbran ni cancelan documentos reales como prueba.

Antes de privatizar se verifican inventarios SHA256 del tag, ZIP de Satis y vendor instalado, reinstalación sin caché y sin Packagist, compatibilidad de plataforma y regresión completa. El constructor dispone de llave dedicada de lectura para GitHub privado. La publicación conserva los artefactos previos y activa el catálogo mediante enlace atómico bajo bloqueo. No se reemplazan tags ni ZIP publicados.

El despliegue solicitado comprende Aguaban y facturacion.ivitec.mx, después de aprobar desarrollo local. Debe conservar configuración, formatos particulares, permisos y datos fiscales; esta migración no requiere DDL, nuevas acciones ni otorgamiento de permisos. Se conservan respaldos de archivos/BD antes de activar y se comprueba HTTPS y ejecución PHP-FPM. Los consumidores antiguos restantes requieren migración de metadata antes de nuevas instalaciones.
