# Color del menú por instalación

La configuración opcional de cada instalación puede declarar:

```php
public static string $menu_superior_color = '#FFF6D8';
```

Se admite exclusivamente una cadena `#` seguida de seis dígitos hexadecimales, sin espacios, saltos de línea, abreviaturas, transparencia ni declaraciones CSS. La propiedad debe ser pública y estática.

- Si la propiedad no existe, se conserva la compatibilidad con `menu_superior_tema`: `amarillo-pastel` o diseño original.
- Si está declarada pero es inválida, privada, no estática, nula o no inicializada, se usa el diseño original. No se recurre al tema pastel en ese caso.
- Un color válido activa `data-iv-theme="personalizado"`. Sólo se emiten variables con el hexadecimal normalizado y texto negro o blanco.

El texto normal elige negro o blanco según el mayor contraste calculado con luminancia relativa sRGB (fórmula WCAG). La fila secundaria utiliza el mismo fondo. Los enlaces normales son transparentes; las acciones y el botón de menú conservan fondo blanco y texto oscuro. Hover y activo mantienen sus fondos claros y texto morado. El contorno de foco usa el color de contraste seleccionado y los bordes son grises tenues fijos. No cambian enlaces, permisos ni distribución.

El ajuste corresponde a configuración local de la instalación, excluida del repositorio. El código versionable no incluye configuración privada ni modifica el archivo de ejemplo. El despliegue cambió únicamente la propiedad visual de FC3, configurada como `menu_superior_color = '#FFF6D8'`. Para volver al comportamiento anterior, retirar la propiedad del color; para garantizar el original, configurar el tema legado como original y retirar el color.

## Verificación
La regresión pura del menú ejecuta 12 casos del selector legado y 20 casos de color, contraste y precedencia. Rechaza tipos incorrectos, propiedades inaccesibles y cadenas maliciosas, incluido un salto de línea final. Comprueba que todas las reglas CSS nuevas estén limitadas a los temas explícitos. No carga la aplicación, configuración real ni base de datos.

Lint PHP y comparación del resto del template completan la revisión estática. El despliegue se realizó exclusivamente en FC3. La revisión visual posterior cubrió escritorio; no sustituye pruebas de navegación móvil ni de todos los estados interactivos.

La limitación inicial de navegador quedó superada para la QA autorizada de escritorio. Se comprobó visualmente y mediante DOM el recorrido amarillo `#FFF6D8` → morado `#7028ED` → amarillo, además de abrir y cerrar el menú. Se conservó un recorte de evidencia en Drive. La restauración final recuperó exactamente el archivo de configuración amarillo previo, con hash y atributos coincidentes, HTTP200 y servicio activo.

## Estado del despliegue
El coordinador verificó el despliegue exclusivo en FC3 con respaldo previo, conservación exacta de propietario, grupo y modo y comprobación de legibilidad antes de reemplazar archivos. Conservó las diferencias de iconos que ya existían en ese entorno. Las 32 pruebas contra los archivos preparados pasaron; FC3 respondió HTTP200 y los archivos de los otros entornos permanecieron intactos.

Los dos colores indicados sí se ensayaron en la UI real de escritorio. Los valores inválidos y demás variantes se verificaron mediante pruebas puras, sin aplicarlos a configuraciones reales. No se emuló móvil ni se certifican todos los dispositivos. Esta nota no incluye rutas privadas, hashes de configuración ni contenidos privados de instalaciones.

### Compatibilidad con configuración ausente
El primer caso de la regresión evalúa el bloque real del template contra una clase de configuración vacía: sin `menu_superior_color` ni `menu_superior_tema`, el resultado es `original` sin warnings ni errores. Los errores PHP se convierten en excepciones durante la prueba. El segundo caso, sin color pero con tema legado pastel, conserva `amarillo-pastel`; así se distingue ausencia total de configuración y compatibilidad con una instalación ya configurada.
