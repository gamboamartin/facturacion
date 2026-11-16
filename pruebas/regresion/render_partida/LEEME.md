# Preparación y render único: prototipo local

Copia anterior recuperable: `/home/em3/partida-catalogo-candidato-cbda37c-20261116` (87 pruebas). Nueva copia aislada, sin commit, push ni producción.

## Cambios
- `_ctl_partida::prepara_alta` extrae init_alta, configuración de selects y los cinco defaults, sin renderizar inputs.
- `alta(bool $header, bool $ws=false): array|string` mantiene firma, retorno del template, secuencia de preparación y un único render, incluidas envolturas de error/header/ws.
- `_base_system_fc::nueva_partida_inicializa` usa preparación sin render previo únicamente en el contexto exacto Factura+Partida ya delimitado. Configura factura seleccionada/deshabilitada, precios y fiscal antes del único inputs final.
- NC/CP y clases personalizadas conservan la ruta previa de alta y segunda configuración. No se cambia constructor Traslado ni propiedades públicas existentes.
- Método auxiliar nuevo no requiere nuevas acciones/ACL, no modifica datos, y no se añadió a permisos ni menús.

## Unitarias
Ejecutar `python3 pruebas/regresion/ejecutar_phpunit.py`.
Resultado: 94 pruebas, 293 aserciones, EXITO_UNITARIO. Se conservan sin cambios de expectativa las87 previas. Siete nuevas: retorno/template/secuencia, preparación sin render, defaults y campos previos, fallo init, fallo selects, fallo inputs con flags y alta repetida. Colaboradores dobles; métodos de aplicación reales.

## Integración del método real
Comparación recuperable:
- `/home/em3/factura-render-metodo-original-20261116/metodo1.json`
- `/home/em3/factura-render-metodo-unico-20261116/metodo1.json`

Constructor real de Partida bajo GET fc_factura/modifica; método real privado nueva_partida_inicializa de Factura. Reflection solo proporciona estado del controlador padre (conexión, registro, errores y Partida), no sustituye método ni ORM. No se ejecutó modifica completo ni HTTP.

Ambos producen inputs serializados idénticos: `c4abf93f3ad0a440f35a650caafa903adedef4adf665b1251442a496127955c9`, mismos defaults,35 opciones producto y2 factura, GET intacto. Original55 consultas/~223ms; único53/~192ms. Datos ficticios, cuenta SELECT-only, servidor aislado sin red, datos intactos y apagado verificados. Tiempos orientativos solo sintéticos. Wrapper externo conserva limitación de cuatro namespaces ajenos ilegibles/exit2.

## No confundir con trabajo terminado en producción
Frontend, formulario completo de Factura, navegación y NC/CP reales en entorno designado siguen pendientes. Las mejoras de paquetes system (ASC) y administrador (N+1 secciones) están en diffs aislados aparte, no instaladas en este candidato. No afirmar que94 unitarias certifican esas dependencias propuestas. Antes de despliegue: revisión, compatibilidad, respaldo recuperable, recuperación y pruebas visuales. Martín autoriza commit/push después de revisar, y siempre se corre batería completa vigente antes de commit.

## Errores del método real, verificados después
Seis ensayos pareados en `/home/em3/factura-render-error-{original,unico}-{duplicado,sql_precio_cliente,sql_producto}-20261116/error1.json`.
- Duplicado: ambos rechazan con Error al obtener precio / Error existe mas de un precio.
- Fallo SQL precio cliente: mismos mensajes y causa, una sola inyección, sin reintento.
- Fallo SQL producto: ambos retornan error, conservan causa y GET, una sola inyección. Cambia contexto diagnóstico: antes fallaba dentro del primer render descartado (template/inputs/selects); ahora en lectura necesaria del catálogo (Error al obtener productos). No se simulan capas de llamadas que ya no existen. No es identidad literal de todos los mensajes.
- Datos ficticios intactos y motor apagado en los seis casos. No se alteraron pruebas anteriores para aceptar estos resultados: son observaciones comparativas nuevas y documentadas.

Plan bloqueado de revisión/despliegue en `PLAN_DESPLIEGUE_BLOQUEADO.md`. Diff exclusivo de este bloque: `/home/em3/partida-plan-dependencias-20261116/render-unico.patch` (git apply --check válido sobre candidato87).
