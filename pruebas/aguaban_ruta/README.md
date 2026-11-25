# Integración de rutas Aguaban

Ejecutar en una copia QA aislada, con las dependencias del candidato y los recursos del laboratorio configurados:

```sh
python3 pruebas/aguaban_ruta/ejecutar.py
```

Requiere `QA_CSD_ENGINE_ROOT`, `QA_CSD_DEPS`, `QA_CSD_DOCUMENTO`, `QA_CSD_PLUGINS` y `QA_CSD_DDL`, igual que las demás suites del motor efímero. El cierre requerido es `PASS_AGUABAN_RUTA_INTEGRACION`, código cero, con los **46 casos / 225 aserciones** de la primera ejecución aprobada. Las identidades y su orden se comparan con `registro.json`; las aserciones se informan, sin convertir el conteo histórico en sustituto de contratos.

La infraestructura inicia una instancia MariaDB nueva dentro de namespaces sin red y sin montajes escribibles del host. Configuración y fuentes se montan en solo lectura. `config/` contiene únicamente configuración ficticia para ese motor y para el constructor de `controler`; nunca se carga configuración privada. No se usan credenciales del entorno local ni sesiones PHP reales. El runner exige identidad del motor, cierre limpio, limpieza de tablas, ausencia de procesos remanentes y SHA256 de las fuentes antes/después y de las clases reales cargadas.

`esquema_acl.json` conserva los metadatos de seis tablas administrativas y doce tablas de branding provenientes del fixture ORM existente, con sus hashes y procedencia; también tres tablas administrativas (sistema, pertenencia y sesión) extraídas únicamente mediante SHOW CREATE/COLUMNS/FK, sin filas. `adm_accion_grupo` se construye con un DDL sintético explícito y claves foráneas reales; no es una extracción de filas de desarrollo. Los IDs ficticios de administrador, grupos y actores difieren de los de desarrollo para detectar dependencias de IDs fijos.

Los casos ejecutan la migración, el modelo PDO independiente, el verificador de permisos y el controlador reales. Cubren las once columnas, índices y FK solicitados, plan sin escrituras, aplicación e idempotencia, revocaciones conservadas, rechazo de permisos ajenos y esquema incompatible; alta, consulta, modificación, estado y eliminación; límites Unicode, campos internos protegidos, auditoría y versión; filtros parametrizados, comodines literales y paginación. Se conserva un modo SQL estricto explícito.

El controlador se construye mediante la fábrica real de `base\controller\init`, con su constructor heredado. Se ejecuta `salida_data::salida_ws` y se renderizan las vistas del proyecto. Solo se utiliza Reflection para resolver la función privada `include_action` del inicializador, sin reemplazar su lógica. Las acciones de escritura rechazan GET y CSRF ausente, falso o mal formado; los permisos se revocan en el catálogo durante la prueba. También se comprueban mensajes/formularios, escape HTML, ausencia de botones sin permiso, conservación de filtros y protección del estado al retirar ese permiso.

El caso de navegación renderiza el sidebar y el menú superior reales con links_menu y modelos administrativos reales, convierte avisos PHP en errores y exige el módulo Aguaban y la sección Rutas visibles. La suite no acredita un login de navegador real ni toda la plantilla global del sitio; esa capa se verifica aparte en desarrollo. No llama al PAC, envía correos ni crea documentos fiscales. Tampoco certifica esquemas ajenos al contrato explícito ni altera permisos existentes para conseguir un PASS.
