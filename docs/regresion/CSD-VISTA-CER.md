# Vista de certificados CER: candidato en copia

## Defecto y alcance
La vista Modifica CSD incluía una tabla estática sin carga de documentos. La vista y el método coincidían con la base anterior al cambio de conversión. El candidato muestra exclusivamente metadatos CER asociados al CSD consultado. No gestiona todos los archivos: no presenta KEY/PEM, rutas, bytes ni enlaces de descarga.

El acceso mantiene la sesión validada del dispatcher y el permiso existente `fc_cer_csd/get_data`, que utiliza el DataTable de Subir CER. No se inventa permiso `lista`. El modelo conserva su seguridad predeterminada; se filtra por CSD y se valida la pertenencia de cada fila. La descripción se escapa al renderizar. No se inicializa otro endpoint.

## Evidencia aislada
- Doce casos nuevos de servicio y plantilla reales con dobles de ACL/ORM: PASS, registrados en el agregado.
- Consulta y alias reales contra ORM con tres escenarios sintéticos y rollback: PASS; motor cerrado.
- Agregado: 155 unitarias, 46 focales CSD, 3 ORM, 1 FPM local, 12 de vista y 21 frontend: PASS.
- El primer agregado quedó bloqueado por variables de fuentes no configuradas. Se preservó el informe, se informó y se repitió con las fuentes acreditadas. No cambió ninguna expectativa.
- No se ha validado esta vista candidata en navegador ni con otra cuenta real.

## Despliegue y recuperación pendientes
El ensayo local del componente de cuatro archivos sustituyó dos copias existentes y creó dos nuevas; restauró hashes previos y retiró las nuevas. No es respaldo remoto ni despliegue.

**No aplicar el componente de cuatro archivos sobre el timer del despliegue anterior:** el controlador comparte destino y habría conflicto de hashes. La coordinación prevé dejar restaurar el baseline y preparar un único paquete de doce archivos (siete existentes y cinco nuevos), con respaldo remoto verificado, guardias, reversión única y recarga exclusiva de FPM. No cambiar nginx, ACL ni Composer para esta vista.

## Revisión UI propuesta, aún sin ejecutar
Usar la sesión autorizada existente en FC3, sin apertura de red adicional. Preparar sólo cuatro maestros ficticios propios: calle, domicilio, empresa inactiva y sucursal activa. Una alta CSD con certificado/clave íntegramente ficticios permite comprobar CER visible, persistencia tras recarga y ausencia de rutas/descargas. Inventariar el grafo real, previsiblemente 22 filas y cuatro archivos, antes de limpieza. La restauración DB no se presume probada: el retiro debe cumplir la autorización y confirmación aplicables. No usar LIAM, certificados reales, PAC, timbrado ni pagos.
