-- Información fiscal capturada por factura; sin valores predeterminados de periodo.
-- Aplicar solo después de verificar que las tres columnas están ausentes.
-- Si ya existen, contrastar tipo, nullabilidad y default; no repetir el ALTER.
ALTER TABLE fc_factura
    ADD COLUMN global_periodicidad VARCHAR(2) NULL DEFAULT NULL,
    ADD COLUMN global_meses VARCHAR(2) NULL DEFAULT NULL,
    ADD COLUMN global_anio VARCHAR(4) NULL DEFAULT NULL;
