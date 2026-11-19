# Logo del sistema y ausencia en blanco

Contrato solicitado el 19 de noviembre de 2026: el logo proviene de los registros del sistema; si no existe una imagen válida, su espacio queda vacío. No se solicita subir una imagen para resolver una ausencia normal ni se añade un logo ficticio. La marca IVITEC del proveedor es independiente del logo de empresa.

## Corrección de causa

- `logo_service.php` exige empresa activa única, logo activo principal único, asociación de empresa/documento y tipo 9. Nombre y logo ignoran selectores GET y comparten el criterio de empresa del sistema.
- El lector mantiene almacén canónico, bloqueo de traversal/symlinks, archivo regular, máximo 2 MiB y MIME PNG/JPEG. Añade decodificación real GD, hasta 8192 por lado y 16 × 1024 × 1024 píxeles antes de reservar la imagen.
- No se devuelve URL estática cuando faltan archivos, la imagen es inválida o la selección es ambigua. Esos estados devuelven `null`; no se intenta escoger otro logo.
- Login deja el contenedor vacío en ausencia. Encabezados y pie consultan exclusivamente el helper; un override en el controlador no puede introducir otra imagen.
- Las consultas opcionales preservan el contexto previo de `errores::$error`, `errores::$out` y `$_SESSION['error_resultado']`. Error previo implica no consultar. Fallos técnicos registran `[facturacion.branding]` con etapa, sin SQL, credenciales ni datos privados; no contaminan el menú. Ausencia normal no se registra como fallo.

Las expectativas históricas de fallback se cambian por esta regla explícita del usuario. No se eliminan casos de seguridad ni se omiten fallos para aprobar la batería. El PNG antiguo usado como fixture tenía cabecera válida pero no decodificaba: se reemplazó por imagen generada/validada con GD.

## Precondición de esquema

El código de administración usa `org_logo.es_principal`, con estados activo/inactivo. El campo faltaba en esta máquina; se añadió previamente con tabla sin logos y respaldo de estructura. El DDL focal queda registrado en `tools/migrations/sql/org_logo_es_principal.sql` y su manifest offline. No se aplica SQL al renderizar ni se elige automáticamente un logo. Consultar el README de migraciones antes de preparar otro entorno.

## Evidencia y métricas

Verificación en copia aislada del commit `ee9bc29` con las modificaciones candidatas, PHP 8.3.17, GD y PHPUnit aislado 10.5.66:

| Comprobación | Resultado |
| --- | --- |
| Reproducción antes del cambio | El render login sin logo falla: esperaba vacío y obtuvo LOGO. |
| Gate PHPUnit y registro exacto | 175 casos, 681 aserciones, 0 fallos/omisiones; EXITO_UNITARIO. |
| Ampliación | 12 casos adicionales de servicio/lector y 8 renders de acceso, menú nuevo, encabezado legacy y pie (incluidos en los 175). |
| Frontend estático existente | 21 pruebas aprobadas. No equivalen a navegador. |
| Inventario offline de migraciones | 15 pruebas aprobadas y manifest/hash válidos; no acredita aplicación en BD. |
| Complementarias CSD focales sin BD | Vista: 12 casos; nombres: 8 casos, aprobados. |
| Modelos y base local reales | Transacción de solo lectura: 0 logos, helper null, nombre del sistema disponible, bandera global false y 0 salidas de error. No se insertaron datos. |
| Revisión independiente | Sin hallazgos accionables pendientes después de corregir fuente única y decodificación. |

El registro `pruebas/regresion/registro.json` mantiene los casos exactos y agrega `logo_render`. Los informes generados se guardan en la copia de verificación, bajo `pruebas/resultados/`. Se conserva evidencia temporal en `/tmp/facturacion-branding-raiz`; no es un almacén permanente.

La batería integral CSD/MariaDB/FPM no se ejecutó: necesita infraestructura y variables QA_CSD_* que no están preparadas en esta sesión. No se declara PASS_BATERIAS_REGISTRADAS ni aprobación de publicación. No se hizo commit, push ni despliegue remoto. Las imágenes presentes se verifican con datos sintéticos; la comprobación real local cubre la ausencia que tiene esta base.
