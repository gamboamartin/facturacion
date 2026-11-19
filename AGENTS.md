# Equipo de agentes de Facturación

## Contexto y orientación

Este repositorio es `gamboamartin/facturacion`: una aplicación PHP con estructura MVC propia, vistas PHP y API. Conserva su arquitectura y convenciones; no presupongas Laravel, React ni un proceso de compilación de Node.

- PHP declarado: `^8.3`. Dependencias y autoload PSR-4 en `composer.json`; versiones fijadas en `composer.lock`.
- Entrada web: `index.php` → inicializador del paquete administrador → controladores/modelos → `principal.php` y plantillas/vistas.
- Backend: `controllers/`, `orm/`, `api/`, `pac/` y clases HTML en `templates/directivas/`.
- Interfaz: `views/`, fragmentos de `templates/`, `js/` y `css/`. Hay archivos mixtos `.js.php`.
- `plantillas/` contiene recursos de documentos; `vendor/` contiene dependencias externas.
- Lee `docs/equipo-agentes.md` para el mapa y `docs/regresion/PROTOCOLO.md` para la política de pruebas. El registro vigente de casos es `pruebas/regresion/registro.json`; los conteos de documentos históricos pueden estar desactualizados.

Trabaja desde la raíz del checkout correcto. Comprueba `git status --short --branch` y `git remote -v` antes de editar. Si el chat empieza en otra carpeta, usa explícitamente la ruta del repositorio y lee sus instrucciones; no crees archivos en un proyecto vacío por coincidencia de nombre. Respeta cambios existentes del usuario.

## Roles y delegación

El agente principal es el **coordinador**. Usa hasta tres especialistas cuando haya tareas independientes útiles; los cambios pequeños pueden resolverse con un solo agente. Los perfiles reutilizables están en `.codex/agents/` y conservan el modelo y permisos de la sesión si no existe una selección explícita.

| Rol | Responsabilidad | Archivos candidatos, sujetos a asignación |
| --- | --- | --- |
| Coordinador | Define alcance, contratos, responsables; integra y verifica el resultado | Instrucciones, perfiles, Composer, puntos de entrada, configuración compartida y documentación general |
| `backend` | Lógica PHP, modelos, controladores, API y adaptadores PAC | `controllers/`, `orm/`, `api/`, `pac/`, `templates/directivas/` |
| `frontend` | Pantallas, formularios, estilos y comportamiento de la interfaz | `views/`, `templates/inputs/`, `templates/nav/`, `templates/inicio/`, `templates/footer/`, `templates/principal.php`, `templates/css.php`, `js/`, `css/` |
| `revisor` | Detecta defectos/regresiones y ejecuta validaciones pertinentes; informa evidencias | Lectura general; escritura de pruebas/documentación solo con asignación explícita |

Los directorios orientan el reparto, pero no conceden propiedad automática de todos sus archivos. `templates/directivas/` contiene clases PHP con acceso a modelos/PDO; no debe asignarse simultáneamente a frontend y backend. El coordinador designa un único responsable para cambios transversales, `plantillas/`, migraciones o configuración.

## Proceso de trabajo

1. Define el resultado observable y revisa los archivos afectados e instrucciones más específicas.
2. Antes de delegar, asigna a cada agente: objetivo, archivos que puede editar, dependencias, comprobaciones y resultado esperado. Mantén la asignación en el chat; no requiere un archivo nuevo por tarea.
3. Acuerda contratos compartidos antes de programar en paralelo: campos, tipos, respuestas/errores, IDs de formulario, selectores y parámetros `seccion`, `accion`, `registro_id`.
4. Dos agentes no editan el mismo archivo simultáneamente. Serializa los cambios compartidos o prepara worktrees si conviene y el entorno lo permite. Un subagente no recibe automáticamente una copia aislada del código.
5. Cada especialista devuelve archivos cambiados, decisiones, comandos ejecutados/resultados y limitaciones. Si necesita otro archivo, informa al coordinador para reasignarlo antes de editar.
6. El revisor comprueba la versión integrada y distingue defectos de sugerencias. El coordinador resuelve hallazgos y ejecuta las verificaciones pertinentes sobre el resultado final.
7. La entrega explica qué cambió, cómo se verificó y qué no se probó. Un plan, una revisión estática y una prueba ejecutada son resultados distintos.

## Convenciones y límites del proyecto

- Mantén namespaces, nombres y contratos existentes, argumentos nombrados y manejo de errores con `errores::$error` cuando corresponda. Revisa la implementación heredada antes de modificar una clase base.
- Comprueba el impacto de `_base_system_fc`, `_transacciones_fc`, `_partida` y `_ctl_partida` sobre facturas, notas de crédito y complementos de pago.
- Conserva `$controlador->inputs`, nombres/IDs de formularios, rutas y selectores usados por JavaScript. Reutiliza jQuery, Bootstrap/selectpicker y DataTables existentes cuando corresponda.
- No conviertas parches en `vendor/` en soluciones persistentes. Los fixtures y parches en `pruebas/regresion/dependencias_partida/` son ensayos de pruebas, no dependencias instaladas.
- No ejecutes `index.php`, endpoints, migraciones o acciones PAC como comprobaciones inocuas: pueden conectar servicios, escribir datos o emitir documentos. Consulta el alcance autorizado y `docs/regresion/CAPAS-PENDIENTES.md` para integración o flujos fiscales.
- No copies secretos ni datos de documentos a instrucciones, pruebas o reportes. La batería unitaria utiliza datos ficticios y no necesita configuración privada, certificados ni credenciales.
- Conserva los lockfiles. Instala las herramientas de pruebas desde su lock; no actualices dependencias de aplicación para preparar agentes.

## Validación

Ejecuta desde la raíz de una copia local aislada del repositorio, como establece el protocolo. Requisitos: PHP 8.3 compatible, Python 3, Composer y extensiones necesarias.

Instalación de herramientas aisladas, cuando falten:

```sh
composer install --working-dir=pruebas/herramientas --no-plugins --no-scripts
```

Puerta de regresión previa a commit:

```sh
python3 pruebas/regresion/ejecutar_phpunit.py
```

Exige `EXITO_UNITARIO` y código cero. El ejecutor contrasta las identidades y el número de casos con el registro, rechaza fallos/omisiones y guarda hashes actuales e informes en `pruebas/resultados/` (ignorado por Git). No compara esos hashes actuales con el baseline histórico del registro. Añade nuevas suites/casos al registro cuando cambie la batería. Investiga fallos y sigue el protocolo para cambios de expectativas; no elimines casos ni ajustes expectativas solo para obtener éxito.

La batería registrada completa se ejecuta con:

```sh
python3 pruebas/ejecutar_completa.py
```

El agregador incluye la puerta unitaria, las suites complementarias obligatorias y los ejecutores de frontend. Requiere el laboratorio aislado y sus recursos `QA_CSD_*` y `FUENTE_*` configurados; no utiliza la configuración privada de la aplicación. Solo `PASS_BATERIAS_REGISTRADAS` con código cero acredita la batería completa. Un recurso ausente deja la ejecución bloqueada; `EXITO_UNITARIO` no sustituye este resultado. Consulta los ejecutores y el registro para conocer los requisitos y el conjunto vigente.

Diagnóstico puntual, sin sustituir la puerta anterior:

```sh
php pruebas/herramientas/vendor/bin/phpunit -c phpunit.xml --filter FacturaTest
```

No sustituyas las herramientas aisladas por `vendor/bin/phpunit`, que carga dependencias de la aplicación. Para PHP modificado usa `php -l` sobre cada archivo y `git diff --check` para revisar el diff. `php -l` también sirve para el segmento PHP de `.js.php`, pero no valida el JavaScript generado. No hay un pipeline Node identificado: no inventes `npm test` o `npm run build`.

La suite unitaria no certifica BD, SQL, herencia completa, navegador, PDF, timbrado ni cancelación. Reporta esas capas como pendientes si son pertinentes. Los tests de migraciones quedan fuera del ejecutor vigente y se evalúan por separado. No reutilices resultados anteriores a cambios relevantes.
