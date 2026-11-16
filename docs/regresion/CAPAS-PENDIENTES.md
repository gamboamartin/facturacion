# Capas de validación y pendientes

## 1. Unitarias: implementadas inicialmente
57 casos de clases FC con dobles y valores ficticios. Sin DB, PAC, CSD ni efectos de negocio. Ejecución rápida antes de cada commit. No equivalen a alta completa, render real ni flujo fiscal.

## 2. Integración: pendiente de diseñar y autorizar
Base desechable con esquema y datos sintéticos, configuración separada, usuarios restringidos y prohibición de destinos productivos. Validar herencia real, SQL, persistencia, relaciones y cálculos. Definir fixtures, limpieza/restauración y resultados esperados con Martín antes de ejecutar. No reutilizar base real ni credenciales productivas.

## 3. Regresión: política transversal
Registrar cada prueba futura y repetir las capas pertinentes ante cambios; la batería unitaria actual es solo la primera parte. Si hay fallo, discutir si cambió negocio o apareció defecto; bloquear commit hasta resolver y documentar. No cambiar expectativas por conveniencia ni usar omisiones como aprobación.

## 4. Flujos completos: pendiente
Navegador y servicios de laboratorio: captura, partidas, totales, PDF y estados de documentos, con entradas sintéticas y expectativas acordadas. Separar de las pruebas rápidas y no afirmar que PHPUnit unitario cubre estos flujos. Añadir comprobaciones cruzadas FC factura y usos compartidos de Partida.

## 5. PAC sandbox: planificación solamente
Requiere confirmar proveedor, endpoint exclusivamente sandbox, credenciales autorizadas y CSD de prueba. Nunca almacenar secretos en Git ni en reportes. Antes de transmitir datos: validar destino, permiso, aislamiento de producción, límites y costo si aplica. Pruebas de timbrado y cancelación únicamente de documentos ficticios en sandbox tras autorización específica. Establecer criterios de UUID/estado/XML/PDF, errores esperados, limpieza y recuperación; no dar por aprobados esos criterios hasta acordarlos con Martín. Ninguna transmisión realizada en esta entrega.

## Puerta de despliegue
Respaldo recuperable verificado, compatibilidad, resultados de capas pertinentes y recuperación documentada. La ubicación de un laboratorio persistente está pendiente de decisión; esta batería local no depende de él. No se habilita despliegue solo por 57 pruebas verdes.
