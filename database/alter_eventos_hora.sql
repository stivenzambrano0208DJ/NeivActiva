USE neivactiva_db;

SET @sql_hora_evento = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE eventos ADD COLUMN hora_evento TIME NULL AFTER fecha_evento',
        'SELECT "La columna hora_evento ya existe"'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eventos'
      AND COLUMN_NAME = 'hora_evento'
);

PREPARE stmt_hora_evento FROM @sql_hora_evento;
EXECUTE stmt_hora_evento;
DEALLOCATE PREPARE stmt_hora_evento;

SET @sql_organizador_id = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE eventos ADD COLUMN organizador_id INT NULL AFTER ruta_imagen',
        'SELECT "La columna organizador_id ya existe"'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'eventos'
      AND COLUMN_NAME = 'organizador_id'
);

PREPARE stmt_organizador_id FROM @sql_organizador_id;
EXECUTE stmt_organizador_id;
DEALLOCATE PREPARE stmt_organizador_id;
