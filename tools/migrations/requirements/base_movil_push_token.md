# base_movil_push_token — BLOQUEADO: definición pendiente

No es una migración. No se encontró DDL fuente en el inventario local revisado.

- Modelo `orm/base_movil_push_token.php:16-22`: relaciones ORM adm_usuario/base_movil_sesion, sin prueba de FK física.
- `api/base_movil/src/app_push.php:81-110`: INSERT token, proveedor, plataforma, adm_usuario_id, base_movil_sesion_id, ultimo_registro; busca token y, si existe, actualiza esos campos excepto token, más status y motivo_baja.
- Líneas188-210: SELECT usuario/token activo/sesión activa y rango de expira; lee id/token.
- Líneas215-221 y `app_sesion.php:423-439`: UPDATE status/motivo_baja por id. Sin DELETE directo en estas rutas.
- Validación de entrada (30-32,69-78): token1..512 bytes y caracteres alfanuméricos/dos puntos/guion/guion bajo; proveedor fcm, plataforma android. No demuestra VARCHAR512, ENUM o UNIQUE en SQL.

Pendiente DDL autorizado: tipos, null/default, índices/unicidad concurrente, auditoría, PK, engine/collation y FK/acciones. No inventar cascada desde sesión ni copiar tokens reales como fixtures.
