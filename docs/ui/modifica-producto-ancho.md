# Modificación producto: ancho adaptable

Original FC3 revisada:1331px viewport/1465px documento.3selectores legibles. Cambio mínimo local: container→container-fluid, sin vendor/global/negocio ni alta. Fuente original FC3=Desarrollo cfc664b739ada9a41354fca4bbab3de43add7e55dcc4aa3d05d259f106f647d9, original override ausente. Histórico conservado. Actualizaciones de Comercial requieren comparar plantilla origen con override.

Martín aprobó ajustar expectativa test_no_modifica_producto: protegía alcance temporal de alta, no regla de negocio. Versión previa conservada fuera del conjunto activo en histórico; contrato activo verifica alta independiente y comparación exacta. Nueva prueba modificación verifica cambio único y hash de alta intacto. Sin rebajar garantía, no se modifica lógica.

Ejecutar batería unitaria vigente y scripts pruebas/frontend/*/probar_*.py, no históricos. Pendiente despliegue candidato FC3 de pruebas con respaldo/rollback, visual sin guardar, revisión Martín antes commit/push.

## Estado FC3
Candidato activo solo en FC3, hash1a786abd2e8317cbc7df7731d7a274ac36cf4415a065ca31affaa38aa21b0431. Respaldo /root/fc3-producto-modifica-ancho-gfbr3224; rollback ensayado y aplicado temporalmente para comparar original. Candidato repuesto; timer transitorio parado e inactive (unidad eliminada por systemd). Ancho1316/viewport1331, antes1465;3selectores legibles. Cierre por botón, filtrosH87/sinresultado/Escape conservan selección;Tab código→precio. Sin guardar, sin móvil. Alta hash intacto. PHPUnit104/415 y21frontend PASS.

Incidencia de revisión visual: pulsar encabezado como cierre exterior tras abrir Unidad cambió selección678→682 tanto con vendor original como candidato. No se guardó; recarga restaura678. Búsqueda yEscape aislados no cambian selección. Causa exacta overlay/automatización no demostrada: no atribuir al contenedor ni declarar cierre exterior PASS. Martín autorizó separar incidencia y terminar revisión de ancho, NO cambiar expectativa. Publicación bloqueada pendiente resolver prueba y revisión Martín.

## Investigación posterior: procedimiento de interacción
La expectativa permanece: cierre exterior legítimo conserva selección. Se midió encabezadoH2 en x326,y282,w919,h30, centro785.5,297; elementFromPoint confirma H2, menú visible debajo, scrollY122. locator.click heading provoca scrollY0 y valor678→682. Clic de puntero en zona visible confirmada, sin scroll automático:350,295;785,297;350,295, tres repeticiones; todas cierran menú, conservan678 y scrollY122. No guardar ni operaciones.
La evidencia acota el fallo a interacción de desplazamiento automático con superposición del menú; no demuestra defecto del cierre exterior de la aplicación. No parche JS ni cambios de expectativa. Procedimiento corregido: abrir menú, tomar screenshot fresco, confirmar que punto exterior pertenece aH2 con elementFromPoint, pulsar punto visible y verificar open0 y valor intacto. Coordenadas son evidencia de esa ventana, no constantes para otros tamaños.
Resultado funcional de cierre exterior real:PASS3/3. Resultado del método locator anterior:fallo reproducido y conservado como diagnóstico de automatización. No se rebaja el contrato ni se elimina evidencia. Pendiente aprobación de publicación; no commit/push.
