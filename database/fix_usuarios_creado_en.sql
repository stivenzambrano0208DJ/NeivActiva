USE neivactiva_db;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(255) NOT NULL,
    documento_identidad VARCHAR(50) NULL,
    telefono VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'organizador', 'cliente', 'participante') DEFAULT 'cliente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario_correo (correo),
    UNIQUE KEY uq_usuario_documento (documento_identidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS documento_identidad VARCHAR(50) NULL AFTER correo,
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) NULL AFTER documento_identidad,
    ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER rol;

ALTER TABLE usuarios
    MODIFY rol ENUM('admin', 'organizador', 'cliente', 'participante') DEFAULT 'cliente';

ALTER TABLE participantes
    ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE inscripciones
    ADD COLUMN IF NOT EXISTS fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS asistencia_en DATETIME NULL,
    ADD COLUMN IF NOT EXISTS asistencia_usuario_id INT NULL;
