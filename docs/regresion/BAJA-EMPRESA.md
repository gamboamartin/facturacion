# Baja de empresa en Facturación

## Regla solicitada

La entidad es `org_empresa`. Su baja debe rechazarse si tiene facturas, complementos de pago, notas de crédito o nóminas asociadas, sin excluir cancelados/inactivos. Los UUID externos son referencias fiscales y también se preservan.

Sin documentos fiscales se retiran las dependencias propias de la empresa en una operación atómica: sucursales (incluida la matriz), departamentos/puestos y sus enlaces, enlaces de logos/representantes/actividades económicas, asociaciones de cuentas de sucursal y configuración de facturación automática propia. No se eliminan catálogos ni las entidades compartidas a las que apuntan esos enlaces.

Los CSD se eliminan mediante el modelo local `fc_csd` y su mecanismo existente: reserva cooperativa, protección de nómina por serial emisor histórico y cascada CER/KEY/PEM/etapas. Se conservan `doc_documento` y los archivos físicos, igual que en la baja independiente de CSD. La configuración de un CSD para nómina por sí sola no demuestra que existan recibos.

## Integración y atomicidad

El controlador local de empresa utiliza el modelo local que hereda de Organigrama. No se modifican archivos de vendor ni el comportamiento independiente de baja de sucursal matriz.

Se inventarían y verifican las relaciones antes del primer DELETE. La operación utiliza transacción propia o savepoint si el llamador ya tiene una transacción. Un error del ORM, un callback que no complete la baja, una dependencia compartida/no clasificada o un fallo intermedio revierte la operación completa y conserva la transacción ajena. Se exige reversibilidad del motor, FK activas y aislamiento compatible. Los errores fiscales indican el tipo de documento que impide eliminar.

Una referencia desconocida no se borra automáticamente. Si el esquema cambia, revisar su pertenencia antes de ampliar la lista de dependencias propias. Un vínculo de subordinación `org_dependencia` puede retirarse si participa un puesto de la empresa; el puesto externo se conserva.

## Verificación

Desarrollo y pruebas se realizan en Facturación local con datos sintéticos y motor efímero. La consulta del esquema de FC3 es de lectura; no se utiliza la eliminación de empresas reales como smoke test. Las pruebas no requieren PAC, credenciales reales ni certificados fiscales válidos.

La suite dedicada se ejecuta mediante `python3 pruebas/empresa_baja/ejecutar.py`, con los recursos de laboratorio indicados por su registro. Se integra en `pruebas/ejecutar_completa.py`; no sustituye la regresión existente ni las pruebas CSD.

Las pruebas del helper sobre PDO real y los contratos de modelo/controlador mediante padres de prueba deben distinguirse de una prueba HTTP autenticada y de la ejecución integral de toda la bitácora del ORM. No se debe presentar una capa como prueba de las otras.

Como comprobación adicional se ejecutó el modelo `org_empresa` con sus padres ORM reales y la cascada CSD real dentro del laboratorio CSD aislado: eliminación de empresa/matriz/CSD/CER/KEY/PEM, conservación de documentos y archivos, transacción exterior abierta y rollback final. Resultado PASS. Esta comprobación suplementaria no ejecutó la ruta HTTP autenticada.

Límite histórico: en el esquema observado la nómina se atribuye al emisor mediante el serial de los CSD de la empresa. Si un CSD fue borrado antes de estas protecciones y ya no existe ninguna relación del emisor, no se puede reconstruir esa atribución desde el modelo actual.
