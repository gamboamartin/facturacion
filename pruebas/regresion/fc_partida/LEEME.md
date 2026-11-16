# Listado de Partida: candidato local, no desplegado

Base: árbol 90162a78e5f0aef10581e8167af9c60e97bca72c de main cbda37c6fb9d7b4bbe36ab2e6ddf9d5ce98a2bb3.

## Cambio y alcance
La vista genérica usa DataTables server-side y no consume la precarga de registros. Solo controlador_fc_partida activa lista_get_data en contexto directo fc_partida (o sin sección explícita); dentro de fc_factura conserva el comportamiento anterior. Recibe explícitamente las nueve columnas existentes a través del constructor _base y del pipeline normal de DataTables, incluidos permisos. El parámetro nuevo es opcional; los otros controladores conservan su comportamiento. No se modifican modelos, totales, inserciones, bajas ni timbrado.

## Unitarias registradas
Ejecutar desde la raíz: `python3 pruebas/regresion/ejecutar_phpunit.py`.
El conjunto mantiene las 57 pruebas anteriores sin cambios y añade nueve casos: tabla única, filtros de factura, constructores NC/CP sin cambio, default de _base, configuración explícita sin mutación y conservación de conexión/rutas/generador, contexto de Factura y contexto directo de Partida. Resultado local: 66 pruebas, 206 aserciones. Son constructores reales de aplicación con dobles de framework/modelos, no certificación integral de permisos ni navegador.

## Integración separada, no laboratorio oficial
Ensayo externo recuperable en Desarrollo: `/home/em3/partida-candidato-real-cbda37c-20261116`, resultado vigente `candidato3-equivalencia.json` (se conservan `candidato1.json` y el fallo `candidato2-contextos.json`). Su ejecutor es `python3 bootstrap_orm_candidate.py`. No ejecutar fuera del entorno validado ni cambiar sus guardas/PINS para hacer pasar errores. Usa MariaDB 10.6.11 verificado, namespaces sin red, tmpfs, runtime de lectura y cuenta exclusivamente SELECT; las tablas y datos son ficticios. Los hashes de archivos y del motor se verifican antes de ejecutar. Conserva el rig anterior por separado.

Pasaron constructor completo, una única configuración (9 datos + acciones), lista sin consultas, get_data paginado, SQL/ACL/render originales, grupo sin botones, exclusión de acciones, búsqueda y filtros. Dos facturas ficticias con 40 y 45 partidas no mezclan resultados; valores de cantidad, precio y total permanecen iguales. El supervisor verificó datos intactos y apagado del servidor aislado. El lanzador exterior no puede inspeccionar cuatro namespaces ajenos del mismo usuario y devuelve código 2; NO se trata como suite global exitosa. El resultado interno y sus límites deben revisarse explícitamente.

Tiempos de referencia sintéticos: constructor 1240.48 ms, lista 0 consultas, get_data factura A 39.56 ms con 10 consultas incluyendo metadatos. No son medición ni garantía de producción.

## Pendientes y puerta de despliegue
- Navegador y laboratorio web designado todavía pendientes. No desplegar solo por resultados unitarios.
- Confirmar pantallas y navegación desde factura; las pruebas de filtro no equivalen a validar el formulario de edición completo.
- No se ejecutaron alta_bd, modificación de importes, pagos, cancelaciones ni timbrado real.
- Defectos anteriores del framework observados: ASC se convierte en DESC y length=0 provoca división por cero. No corregidos por este candidato; el selector normal ofrece 10, 25, 50 y 100.
- Falta plan final de despliegue con respaldo recuperable, compatibilidad/frontend y recuperación documentados. No hay rollback automático, commit ni push autorizados para este candidato.
- Cualquier fallo o cambio de expectativa bloquea commit y se consulta con Martín.

## Expectativa nueva corregida tras comparación autorizada
El ensayo nuevo esperaba 7 columnas en contexto Factura y falló. Se informó y comparó con el constructor original usando idéntico fixture: ambos producen exactamente 6 columnas porque no hay acciones de lista concedidas en ese contexto. Se sustituyó esa única expectativa nueva por comparación exacta con la observación original de sección, bandera, columnas, columnDefs, filtros, tabla única y GET. Las 57 pruebas anteriores permanecen intactas. Evidencia y razonamiento: `comparacion-contexto.md` del rig.

Esta equivalencia cubre el constructor anidado, no el flujo completo de modificación de Factura. El costo del alta, orden ASC del framework y consultas N+1 de precios son problemas separados y no se presentan como resueltos por el cambio de lista.
