# Catálogos comerciales: contratos de interfaz

El usuario solicitó el rediseño de alta y modificación de productos y tipos de producto siguiendo la administración de clientes (25 de noviembre de 2026). Por ese motivo, las pruebas históricas de `alta_producto` y `modifica_producto` conservan sus fixtures y su SHA original, pero sustituyen la comparación de bytes de la vista por contratos del formulario renderizado: destino POST, token, identificador del registro, controles heredados, obligatoriedad, SAT de solo lectura en modificación y escape de contenido.

Estas pruebas usan una configuración sintética en memoria; no cargan credenciales, bases de negocio, CSD ni PAC. Ejecución desde la raíz del proyecto:

```bash
python3 pruebas/frontend/alta_producto/probar_ancho.py
python3 pruebas/frontend/modifica_producto/probar_ancho.py
python3 pruebas/frontend/productos_clientes/probar_catalogos.py
```

El nombre histórico `probar_ancho.py` se conserva para no romper el registro de regresión. Sus aserciones son semánticas; el ancho real requiere navegador. La verificación manual complementaria se realizó en un laboratorio HTTP aislado con MySQL efímero, dependencias instaladas y un usuario del perfil `administracion_clientes`: alta y modificación reales de ambos catálogos, API SAT, configuración fiscal heredada, listas con DataTables finalizado y ausencia de desbordamiento de página en 1366, 390 y 320 píxeles. También se comprobaron texto HTML literal en listas/resultados SAT, parámetros con acentos y `&`, y bloqueo de API sin sesión. No se emitieron comprobantes ni se contactó un PAC real.

La validación de precio del modelo cuenta con su suite separada en `pruebas/producto_precio/`. El laboratorio HTTP comprobó rechazo de texto y desbordamiento tanto al crear como al modificar, además de decimales, cero y negativos válidos. Las respuestas de rechazo deben conservar el producto y sus configuraciones de impuestos.
