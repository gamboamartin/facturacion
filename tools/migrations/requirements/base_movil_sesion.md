# base_movil_sesion — BLOQUEADO: definición pendiente

No es una migración ni autoriza crear la tabla. No se encontró DDL fuente en el inventario local revisado.

- Modelo: `orm/base_movil_sesion.php:14-20`, relación ORM con adm_usuario, no prueba FK física.
- INSERT: `api/base_movil/src/app_sesion.php:317-329`: jti, adm_usuario_id, ip, expira, ultimo_uso.
- SELECT: líneas342-366: filtro jti, exige una fila; lee id, status, adm_usuario_id, ultimo_uso.
- UPDATE: líneas375-408: ultimo_uso; cierre status, cerrada_en, motivo_cierre.
- DELETE: líneas456-475: limpieza probabilística de hasta200 sesiones vencidas hace más de90 días.
- Valores de aplicación: jti hexadecimal32 (línea117), IP hasta45 bytes o vacía (303-310), fechas texto Y-m-d H:i:s. Son contratos de valores, NO tipos SQL.

Pendiente fuente autorizada para tipos, longitudes SQL, PK/autoincrement, null/default, índices/unique, engine/collation, auditoría ORM completa y FK/reglas de eliminación. El supuesto de una fila jti no demuestra índice UNIQUE. Revisar vínculo de tokens antes de elegir políticas de borrado; no asumir CASCADE.
