# Pantallas de cada proyecto

El backend es uno solo para todos los clientes. Lo que cambia por cliente son
sus pantallas, y viven aquí, una carpeta por proyecto:

```
proyectos/
  base/pantallas.php      demostración de la aplicación base (prototipos #1 a #7)
  aguaban/pantallas.php   Aguaban
```

Cada servidor dice de qué proyecto es en `config/generales.php`:

```php
public string $base_movil_proyecto = 'aguaban';
```

Si falta, o el proyecto no cumple lo de abajo, la app no recibe ninguna
pantalla: responde 500 con "La app no está configurada en este servidor" y el
log del servidor dice exactamente qué falta (líneas `base_movil CONFIGURACION:`).

## Crear un proyecto nuevo

1. Copiar `proyectos/base/` a `proyectos/<proyecto>/` (minúsculas, números y
   guion bajo).
2. En `pantallas.php`, cambiar el namespace a
   `base_movil\v1\proyectos\<proyecto>`. La clase se sigue llamando
   `pantallas` y extiende `\base_movil\v1\src\proyecto`.
3. `pantallas()` devuelve `screenId => método`. **Obligatorias:** `login` y
   `home` (la pantalla a la que manda el login, `app_config::PANTALLA_INICIAL`).
   Los métodos de las pantallas van `protected`.
4. **El login debe tener, dentro de un mismo `Form`:**
   - un `Input` con `name` `usuario`;
   - un `Input` con `name` `password` e `inputType` `password`;
   - un `Button` con `action` `array('type' => 'SUBMIT', 'method' => 'login')`.

   Lo demás (logo, textos, colores) es libre. Lo atiende `app_sesion::login`,
   que es el mismo para todos.
5. `pantallas_publicas()` (por defecto solo `login`) y `pantallas_offline()`
   (las que la app precarga para usarlas sin conexión) solo pueden nombrar
   pantallas que estén en `pantallas()`.
6. Agregar `base_movil_proyecto` en el `generales.php` de ese servidor y
   revisarlo con `php -l config/generales.php`.

## Compatibilidad con APKs ya instalados

- Un componente nuevo sale como "no disponible" y una propiedad nueva se
  ignora: no rompen la pantalla.
- Un **valor nuevo** en una propiedad que ya existía sí la rompe (por ejemplo
  `fontWeight '800'` en un APK anterior al 10/11). En pantallas que ya están
  en uso, no usarlo hasta que todos tengan el APK nuevo.
- Las imágenes (`Image`) se registran en la app, en `src/project/images.ts`:
  el backend solo manda la clave.
