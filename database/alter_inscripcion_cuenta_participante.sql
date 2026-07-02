USE neivactiva_db;

ALTER TABLE usuarios
    MODIFY rol ENUM('admin', 'organizador', 'cliente', 'participante') DEFAULT 'cliente';

SET @sql_usuario_documento = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE usuarios ADD COLUMN documento_identidad VARCHAR(50) NULL AFTER correo',
        'SELECT "documento_identidad ya existe en usuarios"')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'documento_identidad'
);
PREPARE stmt_usuario_documento FROM @sql_usuario_documento;
EXECUTE stmt_usuario_documento;
DEALLOCATE PREPARE stmt_usuario_documento;

SET @sql_usuario_telefono = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) NULL AFTER documento_identidad',
        'SELECT "telefono ya existe en usuarios"')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'telefono'
);
PREPARE stmt_usuario_telefono FROM @sql_usuario_telefono;
EXECUTE stmt_usuario_telefono;
DEALLOCATE PREPARE stmt_usuario_telefono;

SET @sql_participante_usuario = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE participantes ADD COLUMN usuario_id INT NULL AFTER id',
        'SELECT "usuario_id ya existe en participantes"')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'participantes' AND COLUMN_NAME = 'usuario_id'
);
PREPARE stmt_participante_usuario FROM @sql_participante_usuario;
EXECUTE stmt_participante_usuario;
DEALLOCATE PREPARE stmt_participante_usuario;

SET @sql_inscripcion_usuario = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE inscripciones ADD COLUMN usuario_id INT NULL AFTER participante_id',
        'SELECT "usuario_id ya existe en inscripciones"')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inscripciones' AND COLUMN_NAME = 'usuario_id'
);
PREPARE stmt_inscripcion_usuario FROM @sql_inscripcion_usuario;
EXECUTE stmt_inscripcion_usuario;
DEALLOCATE PREPARE stmt_inscripcion_usuario;

SET @sql_idx_documento = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE usuarios ADD UNIQUE KEY uq_usuario_documento (documento_identidad)',
        'SELECT "uq_usuario_documento ya existe"')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND INDEX_NAME = 'uq_usuario_documento'
);
PREPARE stmt_idx_documento FROM @sql_idx_documento;
EXECUTE stmt_idx_documento;
DEALLOCATE PREPARE stmt_idx_documento;
