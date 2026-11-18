# Retorno después de Modificar: factura, nota de crédito y complemento

## Corrección publicada

Administrador v22.44.2, commit a339621c69b271f793709a3a589eca7af4aab62d.
El lock cambia exclusivamente ese paquete. No actualiza las demás dependencias.

El método real system::modifica_bd confirma la modificación y llama header_out.
La lectura incondicional de HTTP_REFERER fallaba cuando el navegador no enviaba
esa cabecera. Ahora el retorno explícito tiene precedencia; una cabecera válida
conserva su comportamiento histórico. Si falta, está vacía, tiene tipo inválido
o CR/LF, se construye un retorno relativo con sección, ID y sesión codificados.
No se suprimen errores de persistencia ni se cambian ACL o Referrer-Policy.

No se aplica el candidato anterior de controlador_base: corregía otra ruta.
El único archivo runtime cambiado es administrador/base/controller/controler.php.

## Evidencia y límites

- 36 comprobaciones nuevas ejecutan header_out y system::modifica_bd reales,
  con persistencia simulada y SQL prohibido; arnés legible incluido en administrador.
- 8 regresiones aisladas vigentes de administrador aprobadas.
- 77 comprobaciones históricas preservadas sin alterar; no prueban la ruta nueva.
- Facturación: 108 pruebas y 421 aserciones, más 21 pruebas frontend, aprobadas.
- Las pruebas heredadas destructivas de administrador no se ejecutan contra FC3.
  La batería aislada y esa suite no deben confundirse.
- Pruebas visuales en FC3: una modificación de observaciones por cada documento
  ficticio regresó automáticamente al formulario; recarga y consulta de BD
  confirmaron persistencia en factura, NC y CP. Sin nuevos warnings HTTP_REFERER.
- Un error de formato del arnés se corrigió antes de publicar y se repitieron
  todas las comprobaciones, sin cambiar expectativas ni código runtime.

## Despliegue y limpieza

Archivo original respaldado y restauración de bytes verificada antes de aplicar.
Se conservaron propietario, modo y personalizaciones. FPM exclusivo de FC3
recargado para renovar OPcache. Reversión automática retirada tras aprobación
directa de Martín; respaldo permanece disponible.

La integración final conserva las personalizaciones y exclusiones previas.
El código del paquete instalado coincide con el archivo ya validado visualmente;
los nuevos arneses permanecen en copia privada, no se publican como endpoints.
Los metadatos Composer y lock deben coincidir con la versión publicada.

Se retiraron los 16 objetos ficticios propios: tres documentos, etapas, auxiliares
automáticos de CP y siete maestros. Se verificaron 81 referencias, conteos de
241 tablas y hashes de compartidos; documentos y dependencias de prueba en cero.
El descarte con limitaciones conocidas de triggers/restauración de datos fue
autorizado expresamente. No se tocaron compartidos ni se ejecutaron operaciones
fiscales, pagos efectivos, envíos o cambios a reportes.

Capturas reales quedaron inline; no hay archivos de captura entregables confirmados.
La validación cubre retorno de modificación, no certifica todos los flujos fiscales.
