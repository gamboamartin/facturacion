# Formato de complemento de pago por instalación

La selección del complemento se configura en la clase privada `config\generales` de cada instalación. No se elige por el dominio en el código ni cambia los formatos de factura o nota de crédito.

```php
public ?string $formato_pdf_complemento_pago = 'aguaban';
```

Opciones: `aguaban` usa el diseño de complemento adjunto al proyecto; `estandar` usa el formato general anterior. Si la propiedad no existe o es `null`, se conserva la selección histórica de `proyecto_custom_pdf`; el valor histórico `aguaban` continúa seleccionando el complemento Aguaban. Un valor desconocido se rechaza con un error de configuración.

La plantilla del proyecto es `plantillas/complemento_aguaban.pdf`. El PDF de origen es un fondo sin campos interactivos; los datos se dibujan al generar el comprobante. El cambio no se aplica a documentos guardados anteriormente de forma retroactiva.

La representación usa los datos reales del comprobante y del complemento: serie/folio, emisor, receptor, todos los pagos y documentos relacionados, parcialidad, saldos e impuestos. El XML fiscal es la referencia cuando existe; un borrador conserva su condición pendiente y no recibe UUID, certificados, QR ni sellos inventados. No se timbra ni se modifica el contenido fiscal al generar el PDF.

Los documentos extensos utilizan páginas de continuación para conservar filas y sellos completos. Las pruebas del formato emplean datos sintéticos, sin PAC ni registros de producción.
