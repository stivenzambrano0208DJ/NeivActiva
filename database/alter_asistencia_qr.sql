USE neivactiva_db;

SET @sql_asistencia_en = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE inscripciones ADD COLUMN asistencia_en DATETIME NULL AFTER estado_asistencia',
        'SELECT "La columna asistencia_en ya existe"'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inscripciones'
      AND COLUMN_NAME = 'asistencia_en'
);

PREPARE stmt_asistencia_en FROM @sql_asistencia_en;
EXECUTE stmt_asistencia_en;
DEALLOCATE PREPARE stmt_asistencia_en;

SET @sql_asistencia_usuario = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE inscripciones ADD COLUMN asistencia_usuario_id INT NULL AFTER asistencia_en',
        'SELECT "La columna asistencia_usuario_id ya existe"'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inscripciones'
      AND COLUMN_NAME = 'asistencia_usuario_id'
);

PREPARE stmt_asistencia_usuario FROM @sql_asistencia_usuario;
EXECUTE stmt_asistencia_usuario;
DEALLOCATE PREPARE stmt_asistencia_usuario;
