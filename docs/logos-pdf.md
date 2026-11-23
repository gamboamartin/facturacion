# Logos en los formatos PDF

Los formatos activos de factura, nota de crédito, complemento de pago y recibo de nómina toman la imagen de `org_logo` habilitada para PDF. El logo principal de la interfaz y el logo de PDF conservan sus selecciones independientes: no se altera `es_principal` ni se utiliza un logo de otra empresa como reemplazo.

El proveedor `orm/_logo_pdf.php` consulta exclusivamente logos y documentos activos, de tipo imagen 9, de la empresa del emisor. Si hay varias imágenes habilitadas para PDF, conserva la selección histórica por ID más reciente. En nómina identifica la empresa por el RFC que consta en el XML; si no existe una empresa única, deja el espacio vacío. No usa el CSD global para adivinar el emisor de recibos anteriores.

Las rutas se construyen desde la instalación actual y `doc_documento.ruta_relativa`, no desde una ruta fija de otro servidor. Se reutiliza el lector canónico de imágenes: PNG/JPEG decodificables, sin enlaces ni rutas externas, con límites de tamaño y dimensiones. Una imagen ausente o inválida deja el espacio en blanco, sin icono roto ni marca de reemplazo. Los errores de consulta de esta imagen opcional se registran con mensajes genéricos y no sustituyen errores de la operación fiscal.

Cada formato conserva su caja y motor: factura/NC con plantilla normal usa FPDI y una caja de 35×32 mm en (10,10); complemento estándar conserva el ancho original de su columna (10 %), ajustando proporcionalmente la imagen dentro de una caja virtual de 140×110 sin imponer ancho o altura a la fila; nómina utiliza 140×85 px en la zona libre derecha de la cabecera. Las dimensiones mantienen la proporción original de la imagen. No se cambian importes, datos fiscales, sellos, QR, marcas de cancelación ni diseños de tablas. Las plantillas PDF y los formatos especiales Artistik, IVITEC y Aguaban conservan sus reglas particulares; no se activan otros selectores de formato como efecto de esta integración.

Los cambios se aplican al generar o regenerar el PDF. No se reescriben en lote documentos existentes ni se ejecutan timbrados/cancelaciones para comprobar el logo.

## Verificación

La batería completa incluye contratos del proveedor con PDO simulado e imágenes ficticias, además de generación real de PDF y comparación de páginas, texto, posiciones y regiones de imagen contra las fuentes públicas del commit `0236a47`. Los snapshots comprimidos de prueba no contienen configuración privada ni documentos de clientes. La configuración del logo en una instalación concreta se verifica por separado, con respaldo y sin modificar otras selecciones de `org_logo`.
