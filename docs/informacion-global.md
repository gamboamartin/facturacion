# Información global de facturas a público en general

`fc_factura` permite capturar periodicidad, mes o bimestre y año en alta y modificación. El formulario muestra el bloque al seleccionar un ingreso y un cliente con RFC genérico nacional y nombre PUBLICO EN GENERAL. No se asigna un periodo automáticamente. La combinación ingreso, RFC nacional genérico y nombre PUBLICO EN GENERAL exige estos datos antes de generar el comprobante o llamar al PAC. Las facturas nominativas, notas de crédito y complementos de pago conservan su comportamiento.

El servicio `orm/_informacion_global.php` valida los códigos y agrega `Comprobante.InformacionGlobal` al JSON o el primer nodo `cfdi:InformacionGlobal` al XML que produce el serializador existente. No se modifica `vendor/`, no se renombra el receptor y no se envían comprobantes como parte de las pruebas. La periodicidad bimestral requiere régimen 621 del emisor; el año debe corresponder al actual o a los cinco ejercicios anteriores, sin bajar de 2019.

## Esquema y datos existentes

El DDL registrado es `tools/migrations/sql/fc_factura_informacion_global.sql`: tres columnas opcionales `global_periodicidad VARCHAR(2)`, `global_meses VARCHAR(2)` y `global_anio VARCHAR(4)`, sin periodo predeterminado. El año se trata como código fiscal de cuatro dígitos, no como una magnitud para cálculos.

La ausencia de información admite `NULL` en filas existentes y cadenas vacías al borrar la captura. El ORM heredado conserva NULL al insertar pero lo serializa como cadena vacía al actualizar; el esquema de texto acepta ambas representaciones y el servicio las normaliza antes de validar. No se pretende haber corregido ese comportamiento genérico del paquete administrador. Una captura parcial se rechaza; la API de actualización de este grupo recibe los tres campos juntos.

Antes de desplegar, respaldar estructura y datos, comprobar que las tres columnas están ausentes y ejecutar el ALTER explícitamente. Si existen, contrastar tipo, nullabilidad y default y evitar repetirlo; un estado parcial debe investigarse. No se ejecutan migraciones desde el arranque ni desde las vistas. La incorporación de columnas no modifica facturas ni timbres existentes. El modelo invalida únicamente los dos cachés de columnas de fc_factura una vez por sesión para admitir usuarios ya conectados.

## Validación

Ejecutar la batería completa desde una copia local aislada con los recursos `QA_CSD_*` descritos en AGENTS.md. Incluye contratos del servicio con PHPUnit, render de formularios y laboratorio con BD efímera sin red para guardar, modificar y borrar el periodo usando los serializadores reales del ORM. La generación JSON/XML usa datos ficticios; no constituye una certificación del PAC ni una emisión fiscal real.

La prueba de comportamiento del formulario ejecuta JavaScript con Node y su módulo estándar `vm`, sin paquetes npm. Admite `NODE_BIN` para indicar el ejecutable cuando no esté en PATH; su ausencia bloquea la prueba, no la omite. El contexto de presentación se consulta una vez únicamente al preparar alta o modificación; el navegador recibe identificadores numéricos, no nombres de clientes insertados en atributos HTML.

Fuentes técnicas: [esquema oficial CFDI 4.0 del SAT](https://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd), [actualización de validación de año publicada por Finkok en junio de 2024](https://support.finkok.com/support/solutions/articles/31000173414-cambios-al-anexo-29-de-la-rmf-2024). El periodo de una factura concreta debe confirmarlo el usuario; nunca se deduce de la fecha del servidor.
