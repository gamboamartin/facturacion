# Folios visibles en FINARQ

Ejecutar desde una copia local aislada:

```sh
QA_CSD_DEPS=/ruta/copia/con/vendor python3 pruebas/pdf_folio/ejecutar.py
```

Requiere PHP (GD e intl), dependencias reales de la aplicación, Python con Pillow y Poppler. Genera PDF mediante FPDI y el renderizador real `base_pdf` con configuración y datos ficticios. El doble PDO del fixture no conecta bases; funciones de red y apertura remota están deshabilitadas. No accede a certificados, PAC ni documentos fiscales reales.

El registro contiene ocho identidades: factura y nota de crédito de dos páginas, cada una con folio corto, numérico de 17 dígitos más seis, alfanumérico largo y sin guion. Reconstruye el folio completo a partir del texto extraído y exige que todos sus caracteres estén dentro del cuadro FINARQ (179–199 mm, 0–10 mm) y que sea visible en ambas páginas. Compara todas las palabras ajenas al folio con sus coordenadas y todos los píxeles fuera de ese cuadro contra el documento sin número. Este último también debe coincidir exactamente con el baseline público comprimido de `pruebas/pdf_logo/baseline`, verificado mediante SHA-256; no cambia ninguna expectativa de la suite de logos.

El folio corto debe conservar texto, coordenadas y raster completos respecto al baseline. Para los largos el baseline debe reproducir texto fuera del cuadro; el resultado nuevo debe contener el folio íntegro dentro de él. Esta diferencia es intencional y corresponde al defecto corregido. El número fiscal UUID y los demás textos permanecen sin cambios.

La dependencia original y su copia privada se verifican por inventario SHA-256, antes y después. Una omisión, recurso ausente o diferencia deja la suite bloqueada, sin saltar casos. El resultado se guarda en `pruebas/resultados/pdf_folio_render.json`; PDF/PNG sintéticos en `.git/qa/pdf-folio/render-*`. `QA_PDF_FOLIO_OUTPUT` permite elegir otro directorio privado de evidencias. No certifica paginación arbitraria, otros formatos personalizados ni timbrado.
