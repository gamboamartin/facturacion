# Contratos de entrada y recuperación

Todos son JSON de un operador confiable; las aprobaciones hash no sustituyen autorización humana ni garantizan autenticidad frente a un operador que falsifique los archivos. No alojarlos dentro del webroot.

- **connection:** exactamente socket, database, user, password (strings), archivo regular no symlink y sin permisos para grupo/otros. Socket absoluto; nombre DB alfanumérico/guion bajo. No ejemplo con secretos ni defaults de destino.
- **target:** identity con database, version, server_id, socket, datadir, account, hostname obtenidos del destino real; parent_sha256 del objeto estructural completo adm_usuario según PdoTransport::inspect y digest canónico. No inventar el fingerprint ni aprobarlo automáticamente. Los tipos numéricos de identity deben conservar la representación devuelta por el transporte.
- **journal:** nombre ledger.json, directorio privado existente, rutas confiables sin modificaciones concurrentes. El operador debe respaldarlo y conservarlo. Lock y journal sólo se crean con apply.
- **package approval:** SHA256 de package/manifest.json; cada SQL/estructura también debe coincidir con su hash. Revisión requerida ante cualquier modificación.
- **backup manifest:** identity igual al target y scope exactamente [cc_tipo_medidor,cc_medidor,base_movil_sesion,base_movil_push_token] en ese orden. kind external_backup con restore_status independently_verified para respaldo externo, o synthetic_fixture con fixture_reconstruction_verified sólo para fixture sintético. artifacts contiene1–8 objetos {file,bytes,sha256}, nombres simples relativos al directorio del manifest. El programa verifica archivos/bytes/hash, **no ejecuta ni demuestra por sí mismo la restauración declarada**. Un operador debe revisar evidencia real de restauración ligada a esos bytes, alcance e identidad antes de aprobar el SHA del manifest. No convertir un booleano o una prueba mock en evidencia de producción.

El baseline sintético ensayado restauró el padre ficticio exacto en otro esquema mediante otra conexión del mismo motor efímero. Esto demuestra reconstrucción SQL acotada, no recuperación de una instancia completa ni de datos de producción. El respaldo básico previo de una instalación no equivale a restauración ensayada de este alcance.

Ante fallo parcial: conservar evidencia, ledger y backup; detener. No borrar tablas ni editar el ledger para forzar ready/noop. Una recuperación debe revisarse y autorizarse por separado, incluyendo posibles filas escritas después de crear tablas.
