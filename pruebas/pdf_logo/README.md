# Regresión visual de logos en PDF

Ejecutar desde una copia local aislada del proyecto:

```sh
QA_CSD_DEPS=/ruta/copia/con/vendor python3 pruebas/pdf_logo/ejecutar.py
```

Requiere PHP con GD, DOM, mbstring e intl, las dependencias reales de la aplicación, Python con Pillow y Poppler (`pdftotext`, `pdftoppm`, `pdfimages`). No usa paquetes npm, bases de datos, credenciales, certificados ni servicios PAC. Un recurso ausente o una diferencia devuelve código distinto de cero; no se omiten casos.

El ejecutor acredita 24 identidades de `registro.json`: factura y nota de crédito con dos cabeceras; complemento; nómina normal, de dos páginas y cancelada. Para cada formato genera ausencia de logo, PNG, JPEG y una imagen alta. Las imágenes se crean mediante GD con colores y texto ficticios. La configuración se sustituye explícitamente y PDO es un doble que responde únicamente a consultas del proveedor; los renderizadores FPDI y Dompdf y el proveedor de imágenes son reales.

Compara con tres fuentes del commit `0236a470ed0ffa92ff60e2def3094a2c04261517`, conservadas comprimidas en `baseline/` y verificadas por SHA-256. Esos archivos contienen código, no configuraciones ni documentos privados. Ambos lados se ejecutan en procesos PHP independientes con las mismas dependencias y la misma plantilla FINARQ registrada en el proyecto. Los temporales de imágenes, configuración y render se crean fuera del código de aplicación.

La comparación exige igual papel, páginas y todas las palabras con sus coordenadas, normalizando únicamente el orden de extracción de objetos de Poppler. Renderiza a 72 dpi y exige que todos los píxeles fuera de la caja de logo sean idénticos. En ausencia de logo, factura, NC y nómina son idénticas también dentro de esa caja. Comprueba que la imagen incorporada tiene las dimensiones del fixture y que la marca CANCELADO sigue presente. En complemento, verifica exclusivamente la celda del logo para impedir imágenes vacías; el QR vacío preexistente del pie no se altera ni se presenta como corregido.

Los resultados se guardan en `pruebas/resultados/pdf_logo_render.json`. Los PDF y PNG sintéticos permanecen en `.git/qa/pdf-logo/render-*`; `QA_PDF_LOGO_OUTPUT` permite escoger otro directorio privado de evidencias. Se registran hashes de fuentes, pruebas y baseline y se rechazan cambios durante la ejecución.

Este conjunto comprueba las cajas activas de FC3 y conserva los formatos inactivos Artistik, IVITEC y Aguaban. No certifica la selección SQL contra una base real, la paginación de todos los documentos posibles ni el timbrado. No regenera ni sustituye comprobantes almacenados en producción.
