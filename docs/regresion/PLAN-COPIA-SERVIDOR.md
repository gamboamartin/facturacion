# Plan de copia aislada en servidor: pendiente de ejecución

Destino solicitado por Martín: facturacion.ivitec.mx, carpeta independiente para pruebas unitarias. No copiar ni ejecutar sobre el directorio que sirve producción. No se ha creado esta carpeta ni publicado frontend.

1. Confirmar por inspección de solo lectura ruta de producción, usuario propietario, espacio libre, PHP/Composer y raíz web. Elegir carpeta nueva fuera de la raíz pública y sin enlaces al árbol productivo; nombre y ubicación deben quedar verificados antes de copiar.
2. Generar paquete desde esta copia revisada con manifiesto SHA256. Incluir fuente versionada, suite nueva, phpunit.xml, registro, documentación y Composer de herramientas con lock. Excluir .git, resultados, respaldo histórico, vendor general, cachés, configuraciones con secretos, credenciales y certificados. Auditar archivos de configuración antes del empaquetado; no asumir que todo archivo versionado es inocuo.
3. Verificar que ninguna configuración de conexión ni destino productivo queda en el paquete. Las unitarias usan dobles, no necesitan config/database, PAC, DSN ni datos reales. No ejecutar Composer principal de aplicación ni sus scripts.
4. Copiar exclusivamente a destino nuevo previamente verificado, sin reemplazar carpetas existentes. Verificar hashes después de transferir. Conservar manifiesto y permisos actuales; no cambiar políticas de servidor, red o servicios sin autorización correspondiente.
5. Instalar herramientas Composer desde lock sin plugins ni scripts, o transferir vendor de herramientas verificado para operación sin red. Ejecutar suite unitaria con usuario sin privilegios y sin acceso a credenciales productivas. Si no puede garantizarse separación, detenerse y reportar, no improvisar seguridad.
6. Guardar resultados y comparar identidades, conteos, fallos y hashes con registro. No usar salida verde como permiso para despliegue o para pruebas reales.
7. Frontend de pruebas, base sintética, red/PAC sandbox y aislamiento completo son trabajos separados pendientes de definición. No exponer resultados ni ejecutar PHP mediante una página pública. El traslado a servidor nuevo previsto para miércoles debe llevar este paquete reproducible, no dependencias hacia producción.

Sin commit, push, copia al servidor, cambios de servicios ni transacciones en esta preparación.
