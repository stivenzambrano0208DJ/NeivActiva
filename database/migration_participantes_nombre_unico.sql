USE neivactiva_db;

ALTER TABLE participantes
    ADD COLUMN IF NOT EXISTS nombre VARCHAR(255) NULL AFTER usuario_id;

ALTER TABLE participantes
    MODIFY correo_electronico VARCHAR(255) NULL;

SET @col_nombre_anterior = CHAR(110,111,109,98,114,101,115);
SET @col_extra_anterior = CHAR(97,112,101,108,108,105,100,111,115);

SELECT COUNT(*) INTO @tiene_nombre_anterior
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'participantes'
  AND COLUMN_NAME = @col_nombre_anterior;

SELECT COUNT(*) INTO @tiene_extra_anterior
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'participantes'
  AND COLUMN_NAME = @col_extra_anterior;

SET @expr_nombre_anterior = IF(@tiene_nombre_anterior > 0, @col_nombre_anterior, "''");
SET @expr_extra_anterior = IF(@tiene_extra_anterior > 0, @col_extra_anterior, "''");

SET @sql_nombre = CONCAT(
    "UPDATE participantes SET nombre = COALESCE(NULLIF(nombre, ''), ",
    "NULLIF(TRIM(CONCAT(COALESCE(", @expr_nombre_anterior, ", ''), ' ', COALESCE(", @expr_extra_anterior, ", ''))), ''), ",
    "nombre_completo)"
);
PREPARE stmt_nombre FROM @sql_nombre;
EXECUTE stmt_nombre;
DEALLOCATE PREPARE stmt_nombre;

UPDATE participantes
SET nombre_completo = COALESCE(NULLIF(nombre_completo, ''), nombre),
    documento = COALESCE(NULLIF(documento, ''), documento_identidad),
    correo = COALESCE(NULLIF(correo, ''), correo_electronico);

SET @sql_drop_extra = IF(
    @tiene_extra_anterior > 0,
    CONCAT('ALTER TABLE participantes DROP COLUMN ', @col_extra_anterior),
    'SELECT 1'
);
PREPARE stmt_drop_extra FROM @sql_drop_extra;
EXECUTE stmt_drop_extra;
DEALLOCATE PREPARE stmt_drop_extra;

SET @sql_drop_nombre_anterior = IF(
    @tiene_nombre_anterior > 0,
    CONCAT('ALTER TABLE participantes DROP COLUMN ', @col_nombre_anterior),
    'SELECT 1'
);
PREPARE stmt_drop_nombre_anterior FROM @sql_drop_nombre_anterior;
EXECUTE stmt_drop_nombre_anterior;
DEALLOCATE PREPARE stmt_drop_nombre_anterior;
