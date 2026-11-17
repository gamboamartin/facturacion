# Plan de validación y despliegue: BLOQUEADO

No autoriza ejecutar despliegue, commit ni push. El entorno web de referencia designado por Martín no está disponible. Los rigs aislados no lo sustituyen.

## Separar revisión por cambios
1. Lista Partida: precarga descartada y configuración explícita de columnas, protección del contexto Factura.
2. Catálogo de precios para alta Factura/Partida exactas: consultas agrupadas, reglas de precio/fiscal, errores, sin caché entre usuarios.
3. Preparación/render único: mantiene API alta, defaults, templates, errores y ruta previa NC/CP/custom.
4. Dependencia system: corrección ASC y columna final, compatibilidad pendiente fuera de configuraciones ensayadas.
5. Dependencia administrador: lectura agrupada de acciones por sección, sin alterar ACL/grupo/menú.

Los puntos4/5 solo están en diffs de revisión con pins de base; NO están instalados en el candidato aplicativo ni publicados. No inventar tag/commit corregido. Preferir versión revisada del paquete o fork expresamente autorizado, con composer.lock exacto. No editar vendor a mano en producción.

## Antes de commit
- Revisar diff y evidencia con Martín, obtener autorización explícita de commit/push.
- Comprobar main remoto vigente y no sobrescribir cambios de otro trabajo; candidato parte de árbol90162a78e5f0aef10581e8167af9c60e97bca72c.
- Incorporar pruebas de dependencias a un conjunto ejecutable/versionado cuando se decida integrarlas;94 unitarias no certifican esos diffs separados.
- Ejecutar toda batería vigente. Fallo/omisión/cambio de expectativa bloquea commit y se discute según regla del proyecto.

## Puerta de laboratorio frontend
Confirmar con Martín cuál es el entorno designado vigente, dominio/ruta y base de datos de pruebas. Revisar configuración sin divulgar credenciales. No confundir facturacion.ivitec.mx productivo con laboratorio. No crear nueva instancia ni copiar datos reales por asumir permisos de trabajo anterior.

Instalación limpia y reproducible en laboratorio; usar datos ficticios. Verificar:
- Lista Partida,9 columnas y acciones según permisos, paginación, búsqueda/filtros y ASC/DESC.
- Factura→Partida y vuelta; factura correcta seleccionada/deshabilitada, atributos de producto, precio/fiscal y defaults.
- Cancelar/volver/recargar sin guardar ni crear factura; no ejecutar transacciones/timbrados/pagos/cancelaciones reales.
- NC/CP y controladores personalizados conservan pantallas/comportamiento; al menos dos grupos/usuarios ficticios no ven acciones ajenas.
- Sin warnings PHP, errores JS, pérdida de CSS ni elementos visuales rotos. Medir frío/caliente en navegador y distinguir red/servidor/SQL/render.
- Probar errores controlados en laboratorio, sin ocultar causa ni convertir precio corrupto en cero.

## Puerta de producción, aún no autorizada
- Respaldar recuperablemente archivos/versiones actuales, composer.json/lock, dependencias exactas, configuración y permisos. Copia fuera de ruta pública, sin exposición de secretos. Verificar recuperación, no solo existencia.
- Confirmar destino y DB por confs/database; no hay migraciones de BD ni cambios de sesiones previstos.
- Verificar compatibilidad PHP/Composer y versiones instaladas. Preservar propietarios/modos existentes y comprobar lectura por usuario PHP de cada archivo, para no repetir Permission denied. No chmod recursivo ni permisos amplios.
- Planificar cambio reproducible y mínimo, sin reinicios/opcache/reset de sesiones por defecto. Si fueran necesarios, especificar efecto y aprobación.
- Verificar hashes, versión, permisos y pruebas de vistas permitidas después del cambio. No reportar completado sin confirmación visual.

## Recuperación
Restaurar release/archivos y dependencias anteriores con sus permisos, sin tocar datos de negocio. Plan y respaldo listos antes de despliegue; no ejecutar reversa automática contra instrucción de Martín. Si aparece fallo, informar evidencia y acordar acción concreta. No hay operación DB que revertir en estos cambios.
