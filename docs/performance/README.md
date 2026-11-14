# Rendimiento: investigación y pruebas, no versión desplegable

Fecha: 2026-11-14. Base revisada: `37cb923`.

## Objetivo y estado
Reducir tiempos de login, listados, altas y modificaciones sin alterar importes,
permisos ni consistencia. **Este avance no contiene un parche de rendimiento de
la aplicación ni demuestra una aceleración end-to-end. No desplegarlo.**

La investigación detectó que reducir lecturas fiscales sin revisar transacciones,
concurrencia y recálculos puede cambiar resultados. Por eso se construyeron
oráculos decimales y fixtures sintéticos antes de promover una optimización.
Un microbenchmark aislado no representa la latencia de una operación completa.

## Qué incluye esta rama
- Módulos puros experimentales de asignación exacta, distribución proporcional,
  agrupación de impuestos y serialización de un complemento Pagos sintético.
- Seis escenarios cerrados y expectativas manuales: IVA16, IVA16+8, retención
  aritmética, Exento, IVA16+Exento y Tasa0. ISR10 es sólo un fixture de prueba.
- Pruebas puras reproducibles; manifiesto y lock de dependencia separados del
  proyecto principal. No se incluye código de terceros ni `vendor`.

Las pruebas ORM aisladas verificaron siete casos de creación/rollback y seis
escenarios positivos con XML offline. Sus scripts privilegiados, dumps, datos,
configuraciones y evidencias operativas **no forman parte de este commit**.
Los seis positivos no prueban todos los fallos, writers o concurrencia.

## Ejecutar sólo las pruebas puras
Requiere PHP 8.2 o compatible con el lock, extensión DOM y Composer. En una copia
de desarrollo aislada, nunca sobre la instalación de producción:

```sh
composer --working-dir=lab/v2/deps install --no-plugins --no-scripts --no-interaction
php lab/v2/decimal/ExactDrTest.php
php lab/v2/decimal/test_proportional_tax.php
php lab/v2/first_allocation/test_closed_pipeline.php
php lab/v2/first_allocation/test_closed_scenarios.php
```

Resultados verificados: 51, 454, 64 y 83 comprobaciones respectivamente. No abren
conexiones de base de datos ni llaman PAC. Los identificadores XML son sintéticos;
no hay UUID real, firma, CFDI completo ni certificación fiscal. Los esquemas XSD
oficiales y la validación operacional offline se mantienen fuera de este paquete.

## Prioridad siguiente: rendimiento medido
1. Instrumentar en laboratorio tiempos por fase y cantidad/duración de consultas
   para login, listado, alta y modificación con carga sintética representativa.
   No registrar contraseñas, parámetros SQL ni datos personales.
2. Elegir un cambio pequeño basado en el perfil, por ejemplo eliminar lecturas
   repetidas de catálogos dentro de una petición. No aplicar caché global de
   partidas ni saldos; respetar identidad, permisos, filtros y escrituras.
3. Comparar antes/después sobre el mismo caso, incluir cola larga (p95), consultas,
   memoria y resultados exactos. No sustituir medición por una expectativa.
4. Revisar regresiones y preparar un parche independiente, mínimo y reversible.

Historial fiscal real, notas de crédito por concepto, redondeos heredados,
lectores/writers integrales y persistencia del nuevo circuito siguen pendientes.
Ver [criterios de despliegue](DEPLOYMENT.md).

## Revisión de `.gitignore`
Las reglas principales existentes excluyen configuración DB/PAC, `vendor`,
archivos generados y cachés. Las reglas adicionales del laboratorio excluyen
credenciales, evidencias, respaldos y varios scripts operativos conocidos.
No sustituyen la revisión del manifiesto ni protegen archivos ya rastreados.
Los `.sql` quedan fuera de este paquete experimental; una futura migración
versionada necesitará una excepción explícita revisada. No se usó `git add -f`.
