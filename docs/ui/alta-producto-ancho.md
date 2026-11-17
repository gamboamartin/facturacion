# Alta producto: candidato de ancho adaptable

FC3 17-11-2026: a viewport 1331px la página mide1465px. El main dispone1056px y su container Bootstrap fija1170px. Tres selectores son legibles blanco sobre azul.

Se añade override local views/com_producto/alta.php, idéntico a plantilla Comercial salvo container -> container-fluid. No cambia vendor, CSS global, lógica, formularios ni dependencias. Origen histórico conservado en pruebas/frontend/alta_producto/historico. Confirmado SHA256 idéntico entre Desarrollo y FC3, sin override local previo. Al actualizar Comercial comparar nuevamente la plantilla original y trasladar cambios pertinentes; Composer no sobrescribe este override.

Ejecutar python3 pruebas/frontend/alta_producto/probar_ancho.py, todos los scripts frontend anteriores y python3 pruebas/regresion/ejecutar_phpunit.py. Pruebas estáticas no certifican render. Pendiente visual candidato, móvil, respaldo/rollback y despliegue FC3. No commit/push. Modificación producto presenta el mismo desbordamiento pero se deja separada.

Previo despliegue: verificar ausencia de override y hash vendor, registrar estado recuperable. Aplicar solo vista nueva; rollback moverla a respaldo para recuperar resolución vendor. Validar alta, selectores, cierre/búsqueda sin guardar, ausencia de overflow y navegación. No operaciones reales.

## Validación FC3 completada
17-11-2026: candidato aplicado solo a FC3 de pruebas autorizado tras comprobar original, compatibilidad, PHPUnit104/415,18frontend estáticas y lint PASS. Respaldo /root/fc3-producto-alta-ancho-7lr00w15 en31.220.98.150; original override ausente, vendor copiado/hash verificado, recuperación ensayada; rollback mueve vista nueva al respaldo. Timer precautorio30min parado/inactive tras visual PASS, no ejecutado. Candidato SHA256 de74e33aae0782cb9656eb1936c799c3d844e31743fe95c13a814146de194daf.
Visual: viewport1331 documento1316 (antes1465),3selectores legibles, búsquedaH87/sinresultado, Escape/reapertura/cierre exterior y Tab código→precio. Recarga deja3selectoresvacíos. Sin guardar ni búsqueda ProductoSAT, sin móvil ni operaciones. Sesión segura, datos ficticios confirmados. Antes de instalar la visual del candidato estaba pendiente: instalación fue etapa de validación en entorno designado, no producción. Modificación producto separada intacta. Pendiente revisión Martín, no commit/push.

## Aprobación y publicación
Martín confirmó que alta producto quedó bien y autorizó commit/push. Main remoto avanzó a334ad38 (PDF nota crédito Aguaban); se incorporó por fast-forward sin tocar el cambio aprobado ni mezclar su publicación. Render documental revision-privada excluido. Batería completa se repite antes del commit.
