-- Migración focal de esquema local de listados FC, 2026-11-19.
-- Fuente: SHOW CREATE TABLE / SHOW FULL COLUMNS de FC3, sin datos de negocio.
-- Precondiciones obligatorias descritas en requirements/fc_listas_esquema_20261119.md.
-- No es idempotente: un objeto ya existente debe detener la aplicación y revisarse.
-- DDL MySQL hace commit implícito; ejecutar solo tras respaldo y guardias del esquema.

ALTER TABLE `fc_factura`
  ADD COLUMN `com_tipo_producto_id` bigint NOT NULL DEFAULT '-1';

ALTER TABLE `fc_complemento_pago`
  ADD COLUMN `com_tipo_producto_id` bigint NOT NULL DEFAULT '99999999';

CREATE TABLE `fc_layout_periodo` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `codigo` varchar(200) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'inactivo',
  `usuario_alta_id` int NOT NULL,
  `usuario_update_id` int NOT NULL,
  `fecha_alta` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `descripcion_select` varchar(255) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `codigo_bis` varchar(255) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

ALTER TABLE `fc_layout_nom`
  ADD COLUMN `comision_cliente` double(100,2) NOT NULL,
  ADD COLUMN `sub_total` float(10,2) NOT NULL DEFAULT '0.00',
  ADD COLUMN `total` float(10,2) NOT NULL,
  ADD COLUMN `com_sucursal_id` bigint DEFAULT NULL,
  ADD COLUMN `estado_layout` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'Descarga Layout',
  ADD COLUMN `banco_dispersion` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  ADD COLUMN `envia_nomina_empleado` varchar(255) NOT NULL DEFAULT 'inactivo',
  ADD COLUMN `envia_nomina_cliente` varchar(255) NOT NULL DEFAULT 'inactivo',
  ADD COLUMN `porcentaje_comision_cliente` double(100,2) NOT NULL DEFAULT '0.00',
  ADD COLUMN `fc_layout_periodo_id` bigint NOT NULL DEFAULT '-1',
  ADD COLUMN `asignado_fc_factura` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'inactivo',
  ADD COLUMN `monto_por_asignar` float(10,2) NOT NULL,
  ADD COLUMN `agente_asesor_id` bigint NOT NULL DEFAULT '-1',
  ADD COLUMN `agente_operador_id` bigint NOT NULL DEFAULT '-1';

ALTER TABLE `fc_row_layout`
  ADD COLUMN `esta_cancelado` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'inactivo',
  ADD COLUMN `nomina_enviada` varchar(255) NOT NULL DEFAULT 'inactivo',
  ADD COLUMN `comision_cliente` double(100,2) NOT NULL DEFAULT '0.00',
  ADD COLUMN `porcentaje_comision_cliente` double(10,2) NOT NULL DEFAULT '0.00',
  ADD COLUMN `agente_operacion_alta_id` bigint NOT NULL DEFAULT '-1';
