CREATE TABLE cc_medidor (
  id INT NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(255) NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  codigo_bis VARCHAR(255) NOT NULL,
  descripcion_select VARCHAR(1024) NOT NULL,
  alias VARCHAR(255) NOT NULL,
  status VARCHAR(8) NOT NULL DEFAULT 'activo',
  usuario_alta_id BIGINT NOT NULL,
  usuario_update_id BIGINT NOT NULL,
  cc_tipo_medidor_id INT NOT NULL,
  PRIMARY KEY (id),
  KEY ix_cc_medidor_tipo (cc_tipo_medidor_id),
  CONSTRAINT fk_cc_medidor_tipo FOREIGN KEY (cc_tipo_medidor_id)
    REFERENCES cc_tipo_medidor (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
