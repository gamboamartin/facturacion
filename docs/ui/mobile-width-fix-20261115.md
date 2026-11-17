# Corrección de ancho móvil: Alta y Modifica

El usuario informó que ambas vistas no respetaban el ancho del teléfono. La primera entrega sólo había sido verificada en escritorio y no constituía aprobación móvil.

## Causa y alcance
Alta era un hijo de flex-column con márgenes horizontales auto, sin width explícito ni min-width:0. Eso permite dimensionamiento intrínseco en vez de ocupar el ancho disponible. Se fija width:100%, min-width:0 y border-box, conservando su máximo1280px.
Modifica no disponía de reglas propias para su formulario y tabla de cuatro importes. Se añade un envoltorio acotado y CSS compartido exclusivamente por Alta/Modifica: grupos en bloque y controles a ancho completo en móvil, botones con salto, opciones largas de selectpicker con ajuste, tablas con scroll horizontal dentro del panel. No se oculta el overflow global ni se eliminan controles.

## Verificación
- Revisión independiente estática: sin bloqueo; incorporados sus dos ajustes (máximo1280 de Alta y display:block de grupos móviles).
- PHP lint de ambas vistas: PASS local y staging remoto.
- Bloques PHP comparados exactamente y en orden contra commit anterior: idénticos. No cambia formulario, IDs, callbacks ni cálculos.
- Regresión CP pura:12casosPASS. DiffcheckPASS.
- Activación sólo FC3 2026-11-15T17:59:18Z, hashes4archivos coincidentes, servicio dedicado activo, CSS HTTPS200.
- No se guardaron registros, timbró ni envió nada durante este ajuste.
- Móvil real/emulado pendiente: DevTools cloud bloqueado por organización; no se eludió. No declarar validación móvil a partir de CSS ni saludHTTP. Solicitar al usuario recargar y revisar ambas en su teléfono.

## Reversión
Respaldo privado verificado de Alta.php, Modifica.php y Alta.css previos, con constancia de ausencia del nuevo form-responsive.css. Restaurar esos3archivos, retirar sólo CSS nuevo, reload exclusivo FC3. Sin reversión de DB.
No push a main hasta confirmación expresa del usuario.
