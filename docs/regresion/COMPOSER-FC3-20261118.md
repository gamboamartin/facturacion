# Actualización general de Composer y revisión de Facturación 3

Fecha: 18 de noviembre de 2026. Base publicada: 81bf068fcd11eef64ec15bd770d92c2da8d1eeff.

## Alcance reproducible

Composer 2.8.5, PHP 8.3.36. Resolución e instalación en copia privada con
`--no-scripts --no-plugins --no-interaction`. El cambio del lock respecto a main:
firebase/php-jwt 7.2.1 → 7.2.2, guzzlehttp/guzzle 7.16.2 → 7.16.3 y
guzzlehttp/psr7 2.13.3 → 2.13.4. Sin altas ni bajas respecto al lock de main.
Composer normalizó dos mapas vacíos a objetos JSON. No cambia composer.json.

Lock SHA256: f528ff1667ff13bccc055076c3ecd755f2e2516b78020e24ce21206d5959c7a0.

El piloto Satis gamboa.martin/configuraciones permanece en v1.0.0, misma
referencia de código y origen privado de descarga. No implica privatización
de los demás paquetes.

## Pruebas y límites

- Batería registrada: 108 pruebas, 421 aserciones, aprobadas en copia con dependencias.
- Frontend estático: 21 pruebas aprobadas.
- Plataforma Composer y auditoría: aprobadas, sin avisos ni paquetes abandonados.
- Cuatro comprobaciones aisladas JWT/PSR7/Guzzle aprobadas, sin servicios externos.
- Render sintético Dompdf y carga de tres plantillas FPDI aprobados. FPDF avisa
  de deprecación de definiciones de fuentes PHP; no se corrigió en este alcance.
- Antes y después del despliegue se revisaron listas y altas de factura, nota
  de crédito y complemento. No se crearon documentos después del despliegue.
  No sustituye una prueba fiscal integral ni valida todas las acciones Modifica.

La discrepancia de cantidad en partida NC se contrastó manteniendo expectativa:
teclado guardó 2 y 3 con persistencia y recarga; el método fill mostró 3 sin
actualizar el total ni persistir. El resultado comparativo se discutió con
Martín antes de continuar. No se cambió código ni expectativa por este ensayo.

## Despliegue y recuperación

Se actualizó únicamente el entorno de pruebas Facturación 3, conservando
configuración y personalizaciones locales identificadas. El vendor previo no
coincidía con su lock: la sincronización efectiva supuso 4 instalaciones y
36 actualizaciones respecto a sus metadatos, distinta de los tres cambios del
lock publicado. Se preservaron seis personalizaciones de vendor y el CSS de
recursos locales. No se modificaron reportes ni se activaron módulos opcionales.

Se respaldaron manifiestos/vendor y los archivos runtime reemplazados, y se
verificó extracción y comparación de respaldos antes del despliegue.
Aplicación 14:28 UTC; recarga exclusiva del FPM de FC3 por OPcache sin
validación temporal. Se verificaron 53 hashes runtime, lock, plataforma y salud.
La reversión automática se canceló a las 14:35:59 UTC tras confirmación directa
de Martín; los respaldos y procedimiento de restauración se conservan.

Los módulos móviles siguen sin configuración ni tablas específicas: no se
declaran habilitados. No se ejecutaron migraciones ni operaciones fiscales.
La corrección separada de retorno cuando falta HTTP_REFERER NO está incluida.

## Datos de prueba y evidencia

Los documentos ficticios de las dos rondas NC y sus objetos propios se retiraron
con autorización específica, sin tocar compartidos. Inventario final de factura,
NC, complemento y layout: cero documentos. Las limitaciones de verificación de
triggers y restauración del respaldo de esos fixtures se informaron y aceptaron
expresamente; no se confunden con los respaldos de código verificados.

Capturas de la revisión visibles en la conversación de trabajo, sin archivo
descargable confirmado. No se incluyen datos, contraseñas ni sesiones en Git.
