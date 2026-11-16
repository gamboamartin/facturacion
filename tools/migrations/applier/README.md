# Aplicador explícito de cuatro tablas nuevas

Paquete de **diseño nuevo**, no DDL histórico recuperado ni migración aplicada. El inventario padre continúa siendo offline y no se modifica automáticamente. Este CLI es independiente del arranque web/Composer y requiere PHP8.3 con PDO MySQL; transporte acotado a MariaDB10.6 por socket local. Publicarlo no autoriza ejecutarlo en producción.

## Comandos

Desde la raíz del repositorio:

```sh
php tools/migrations/applier/migrate.php
php tools/migrations/applier/migrate.php plan
python3 -B -m unittest discover -s tools/migrations/applier -p 'test_*.py'
```

Sin argumentos se ejecuta `plan`: sólo archivos del paquete, sin conexión ni escrituras. `check` abre una conexión y consulta identidad/esquema/privilegios; no crea ledger, locks ni tablas. `apply` está operativo, pero exige argumentos y aprobaciones explícitos. No ejecutar ejemplos sustituyendo destinos al azar:

```text
php tools/migrations/applier/migrate.php check --connection CONNECTION_JSON --target TARGET_JSON --journal PRIVATE_DIR/ledger.json
php tools/migrations/applier/migrate.php apply --connection CONNECTION_JSON --target TARGET_JSON --journal PRIVATE_DIR/ledger.json --approve-package PACKAGE_SHA256 --backup BACKUP_MANIFEST_JSON --approve-backup BACKUP_MANIFEST_SHA256
```

Contrato de archivos en CONTRACTS.md. No se incluyen conexiones, credenciales, destinos ni respaldos reales. El operador prepara y revisa los archivos fuera del webroot, con permisos privados. El CLI no captura ni imprime contraseñas.

## Precondiciones y efectos

Las cuatro tablas deben estar ausentes; adm_usuario debe existir con PK id BIGINT firmado NOT NULL/InnoDB y fingerprint completo aprobado. Verificación exacta de columnas, tipos, defaults, índices ordenados canónicamente, FK, checks/triggers y atributos. Cuenta debe tener privilegios suficientes para visibilidad de metadata y creación, explícitamente revisados; el programa no concede permisos. Motor, collation y SQL mode también se comprueban.

Crea únicamente cc_tipo_medidor, cc_medidor, base_movil_sesion y base_movil_push_token; no ALTER, DROP, seed, configuración, usuarios, ACL ni archivos de negocio. FK compuesta push(session,user) impide asociar token a sesión de otro usuario. No implementa reasignación ni política de retención. La limpieza de sesiones con tokens referenciados puede fallar por RESTRICT, deliberadamente sin cascada.

Lock de archivo + GET_LOCK cooperativo; no bloquea escritores externos que ignoren ese lock. Usar ventana controlada. Journal durable antes/después de cada DDL. DDL autocommit: un fallo puede dejar tablas creadas. STOP en incertidumbre/drift; no rollback ficticio, reintento, reanudación ni adopción automática de tablas existentes. `noop` requiere ledger completo y metadata exacta. Preservar journal junto al backup y revisar manualmente cualquier estado parcial.

## Evidencia y límites

Pruebas puras incluidas simulan transporte y fallos; sus fixtures de backup NO prueban restauración. Ensayo aislado en MariaDB10.6.11 comprobó creación/segunda aplicación no-op, drift y fallo parcial sin continuación automática. ORM original push pasó un INSERT bajo los dos DDL compuestos exactos, con JOIN/readback y rollback. Tipo original→medidor corregido pasó dos INSERT con código generado original, retorno/FK/readback y rollback. No se probaron autenticación web/JWT/UI, concurrencia de tokens ni despliegue de producción con este paquete.

El cambio de medidor se limita a alta_bd: usa registro normalizado, valida ID, genera código si falta y delega al ORM padre para campos comunes/auditoría. No modifica RNG ni añade garantía de unicidad. La configuración base_movil_proyecto sigue siendo decisión separada: base es demo, aguaban es específico. No se habilita ninguno aquí.

La ausencia de `base_movil_proyecto` no deshabilita las rutas de login, logout o push: el router las atiende independientemente del proyecto de pantallas. Esta entrega no elige ni configura un proyecto móvil.

## Adaptación de modo SQL de la sesión dedicada
Si la conexión llega con STRICT_TRANS_TABLES, el transporte agrega STRICT_ALL_TABLES mediante SET SESSION, conservando los otros modos y verificando el conjunto final. No cambia GLOBAL ni las conexiones de la aplicación. Rechaza sesiones inicialmente no estrictas. Esto también sucede en check: no modifica datos o esquema, pero sí configura su propia sesión temporal.

Ensayo aislado MariaDB10.6.11 con modo inicial equivalente al destino: apply y noop correctos; modo global y otra conexión intactos. Motor/hijo/launcher terminaron0; wrapper2 por procesos mismosUID cuya namespace no pudo leer. No equivale a limpieza global comprobada.

La protección web de tools/migrations ytests quedó aplicada separadamente en el vhost correcto mediante reglas404. No se presume protección de otros servidores: instalar este paquete fuera de webroot o protegerlo explícitamente. No añadir credenciales,target,journal ni respaldo al repositorio.
