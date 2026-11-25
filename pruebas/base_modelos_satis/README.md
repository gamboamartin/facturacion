# Contrato e integración de base_modelos v5.1.0

Desde una copia QA aislada con dependencias instaladas:

```sh
python3 -B pruebas/base_modelos_satis/ejecutar.py
```

Para seleccionar el vendor del candidato:

```sh
QA_BASE_MODELOS_VENDOR=/ruta/qa/vendor python3 -B pruebas/base_modelos_satis/ejecutar.py
```

También se acepta `QA_VENDOR`; la variable específica tiene prioridad. El cierre requerido es `PASS_BASE_MODELOS_SATIS` con código cero. PHP y Python exigen los 29 casos registrados, su identidad y orden completos; una salida anticipada con código cero se rechaza. Se informan aserciones, PHP, SHA256 de nueve fuentes reales y versión/referencia/hash del manifiesto de seis dependencias instaladas. La identidad de artefacto/tag y transporte Satis se verifican por separado.

Los casos ejecutan los dos métodos públicos finales de `gamboamartin\base_modelos\base_modelos` con argumentos nombrados y la herencia real de `gamboamartin\validacion\validacion`: acción/sección vacías, prefijo `models\`, activo/inactivo, normalización del nombre de tabla, ID cero/negativo, status ausente/null/de otra tabla, permiso explícito, datos conservados y contrato real de `errores`.

La integración ejecuta `base\controller\valida_controller` real como descendiente del paquete. También ejecuta la lógica privada `base\orm\upd::reactiva()` mediante Reflection y comprueba el rechazo de un registro inactivo con su causa original, además de activo y permiso explícito. El modelo es una instancia real de `base\orm\modelo`, creada sin constructor y provista únicamente de cuatro propiedades públicas ficticias. No se sustituye ninguna clase; no se inicializa PDO ni se abre una conexión. Esta prueba no acredita el constructor completo ni persistencia.

`gamboamartin\system\datatables\validacion_dt` hereda de `validacion` singular, no de `base_modelos`. Su caso acredita compatibilidad de la dependencia común y errores; no se presenta como consumidor directo del paquete migrado.

Se usa Composer ClassLoader real y el mapa PSR-4 generado, restringido a nueve clases/fuentes permitidas. No se carga el autoload global, archivos de configuración privada ni `gamboamartin\test\test`, cuyo constructor abre conexiones. La identidad instalada y el manifiesto de `test` se registran sin ejecutarlo. `$_SESSION` es solo un array en memoria para comprobar el contrato de errores: no se inicia sesión PHP. No hay BD, SQL ejecutado, PAC ni documentos fiscales.

## Límites del contrato conservado

- La lista verifica cadenas vacías y elimina `models\`; no valida existencia de acciones/secciones ni rechaza espacios. No acredita permisos de usuario.
- `aplica_transaccion_inactivo=true` omite ID y status, pero sigue exigiendo tabla no vacía. No equivale a un control de autorización.
- Con el permiso desactivado se rechaza exactamente `inactivo`; otros valores de status no nulos se admiten, incluido vacío. Esta migración conserva ese comportamiento.
- Un método exitoso no borra un error previo del llamador. Para ese caso se instancia primero el validador: el constructor heredado termina prematuramente si ya existe un error global; no se modifica ese comportamiento histórico.
- La comparación del paquete con el tag, el autoload completo/optimizado, la descarga autenticada de Satis y la batería completa de Facturación son verificaciones adicionales a esta suite.
