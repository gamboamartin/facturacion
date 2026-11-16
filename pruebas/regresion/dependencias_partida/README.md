# Propuestas de dependencias: pruebas aisladas

Ejecutar python3 pruebas/regresion/ejecutar_phpunit.py desde cualquier directorio. Suite vigente: 100 pruebas / 403 aserciones. Cuatro pruebas nuevas ejecutan arneses PHP locales sin red ni BD: 12 casos base de order, 11 precedencias/targets, 8 casos de secciones. Se preservan las expectativas anteriores y se exige que los 2 fallos del candidato anterior permanezcan demostrados. Los archivos init/adm aquí son fixtures de propuesta, NO dependencias instaladas.

Fuente: revisión independiente /home/em3/partida-dependencias-revision-independiente-20261116; copias originales y propuestas anteriores incluidas para reproducción portable. Los scripts ya no dependen de rutas absolutas externas. Se conserva resultado de comparación ACL real como evidencia, NO se ejecuta MariaDB desde PHPUnit ni se finge que el mock certifica SQL. El rig SQL separado usa datos ficticios SELECT-only y verificó apagado; wrapper exit2 por namespaces ajenos ilegibles.

## Consumidores y límite
Inspección de aplicación completa del candidato (excluyendo pruebas) encontró columnDefs en templates/principal.php (solo responsivePriority para acciones) y js/fc_layout_nom/ver_empleados.js (targets array numérico, DataTable local). Generador real system src/datatables.php::columns_defs tiene targets tipado int, invocado por genera_column: columnas numéricas y acciones negativas. Las asignaciones pestañas->targets del administrador NO son DataTables.

No se encontró consumidor CSS/class targets en aplicación ni closure de dependencias usada por fixtures. Esto NO demuestra ausencia en consumidores externos del paquete. El método corregido no resuelve selectores CSS/clases ni nombres: ignora dichos targets. No publicarlo como compatibilidad global completa. No inventar metadata DOM ni evaluar CSS en PHP.

Alternativa evaluada y aún no aplicada: si alguna definición usa target fuera de int/_all, ejecutar método histórico exacto para toda tabla. Conserva comportamiento legado pero también ASC defectuoso, exclusión de última columna y mutación GET. Limitaría mejora al contrato numérico de Partida. Requiere pruebas resultado+GET+errores equivalentes y documentar alcance antes de publicar.

Administrador mantiene lote para IDs canónicos y ruta histórica completa para IDs no canónicos. Se conserva ACL posterior sin atajos. El fallback es de compatibilidad pública, no una nueva autorización.

Ninguna publicación, commit o despliegue. Laboratorio frontend y aprobación siguen pendientes.

## Ensayo de fallback incorporado
Propuesta adicional init_compatible.php y system_compatible.patch: detecta cualquier target fuera de int/_all y aplica literalmente order histórico a toda tabla. 27 comparaciones exactas salida+GET+warnings+Throwable pasan (8 formas no representables por 3 solicitudes, más 3 columnas numéricas). Además vuelve a ejecutar los 23 casos numéricos sin alterar expectativas. Se conserva evidencia anterior y propuesta corregida sin fallback. El contrato desconocido conserva defectos históricos: NO afirmar ASC global arreglado ni GET inmutable para CSS. Ninguna dependencia instalada.

ORM real del system compatible verificado en /home/em3/partida-orden-compatible-orm-20261116/compatible1.json: ASC primera fila1, DESC85, 10 filas, acciones rechazadas, última columna de datos válida; filtros factura A/B, importes, ACL y configuración constructor conservados. Primario correcto, baseline sin cambios y servidor detenido. Advertencia wrapper exit2 permanece. No HTTP/frontend.
