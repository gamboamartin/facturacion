-- Metadatos operativos: no cambian facturas, XML, series, folios ni certificados.
-- Sin FK a facturas/usuarios: el historial de resultados sobrevive a su baja y no
-- agrega restricciones a la eliminación de facturas pendientes ni de empresas.
CREATE TABLE fc_timbrado_lote (
    id CHAR(32) NOT NULL PRIMARY KEY,
    usuario_id BIGINT NOT NULL,
    clave_solicitud CHAR(32) NOT NULL,
    huella CHAR(64) NOT NULL,
    filtros TEXT NOT NULL,
    fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY lote_solicitud (usuario_id,clave_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fc_timbrado_lote_item (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    lote_id CHAR(32) NOT NULL,
    factura_id BIGINT NOT NULL,
    posicion INT NOT NULL,
    empresa_id BIGINT NOT NULL,
    folio VARCHAR(255) NOT NULL,
    estado VARCHAR(24) NOT NULL DEFAULT 'pendiente',
    mensaje VARCHAR(500) NOT NULL DEFAULT '',
    uuid VARCHAR(64) NOT NULL DEFAULT '',
    codigo VARCHAR(64) NOT NULL DEFAULT '',
    fecha_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY lote_factura (lote_id,factura_id),
    UNIQUE KEY lote_posicion (lote_id,posicion),
    KEY factura_historial (factura_id),
    CONSTRAINT timbrado_item_lote FOREIGN KEY (lote_id) REFERENCES fc_timbrado_lote(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- XML recuperable solo en BD, no se incluye en vistas/JSON del lote ni en logs.
CREATE TABLE fc_timbrado_intento (
    factura_id BIGINT NOT NULL PRIMARY KEY,
    intento CHAR(32) NOT NULL,
    estado VARCHAR(24) NOT NULL,
    uuid VARCHAR(64) NOT NULL DEFAULT '',
    codigo VARCHAR(64) NOT NULL DEFAULT '',
    mensaje VARCHAR(500) NOT NULL DEFAULT '',
    xml_respuesta MEDIUMBLOB NULL,
    fecha_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
