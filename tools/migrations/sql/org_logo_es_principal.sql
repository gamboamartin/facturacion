-- Precondición: org_logo existe y carece de es_principal.
-- Aplicación explícita por operador, nunca durante render ni arranque.
-- No selecciona logos ni modifica registros existentes.
ALTER TABLE org_logo ADD COLUMN es_principal VARCHAR(8) NOT NULL DEFAULT 'inactivo';
