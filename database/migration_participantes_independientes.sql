USE neivactiva_db;

CREATE TABLE IF NOT EXISTS participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nombre VARCHAR(255) NOT NULL,
    documento VARCHAR(50) NULL,
    nombre_completo VARCHAR(255) NOT NULL,
    correo_electronico VARCHAR(255) NULL,
    documento_identidad VARCHAR(50) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    correo VARCHAR(255) NULL,
    fecha_nacimiento DATE NULL,
    genero VARCHAR(30) NULL,
    ciudad VARCHAR(120) NULL,
    institucion VARCHAR(180) NULL,
    observaciones TEXT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_participante_correo (correo_electronico),
    UNIQUE KEY uq_participante_documento (documento_identidad),
    KEY idx_participantes_documento (documento),
    KEY idx_participantes_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE participantes ADD COLUMN IF NOT EXISTS usuario_id INT NULL AFTER id;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS nombre VARCHAR(255) NULL AFTER usuario_id;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS documento VARCHAR(50) NULL AFTER nombre;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS correo VARCHAR(255) NULL AFTER telefono;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE NULL AFTER correo;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS genero VARCHAR(30) NULL AFTER fecha_nacimiento;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS ciudad VARCHAR(120) NULL AFTER genero;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS institucion VARCHAR(180) NULL AFTER ciudad;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS observaciones TEXT NULL AFTER institucion;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE participantes ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE participantes MODIFY correo_electronico VARCHAR(255) NULL;

UPDATE participantes
SET nombre = COALESCE(NULLIF(nombre, ''), nombre_completo),
    documento = COALESCE(NULLIF(documento, ''), documento_identidad),
    correo = COALESCE(NULLIF(correo, ''), correo_electronico),
    nombre_completo = COALESCE(NULLIF(nombre_completo, ''), nombre);

CREATE TABLE IF NOT EXISTS inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    participante_id INT NULL,
    estado ENUM('Confirmada', 'Cancelada') DEFAULT 'Confirmada',
    asistencia ENUM('Pendiente', 'Asistio', 'Ausente') DEFAULT 'Pendiente',
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    codigo_qr VARCHAR(128) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS participante_id INT NULL AFTER evento_id;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS estado ENUM('Confirmada', 'Cancelada') DEFAULT 'Confirmada' AFTER participante_id;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS asistencia ENUM('Pendiente', 'Asistio', 'Ausente') DEFAULT 'Pendiente' AFTER estado;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS codigo_qr VARCHAR(128) NULL AFTER asistencia;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS ruta_qr VARCHAR(255) NULL;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS token_qr VARCHAR(128) NULL;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS datos_qr VARCHAR(255) NULL;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS estado_inscripcion ENUM('Confirmada', 'Cancelada') DEFAULT 'Confirmada';
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS estado_asistencia ENUM('Pendiente', 'Asistio', 'Ausente') DEFAULT 'Pendiente';
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS asistencia_en DATETIME NULL;
ALTER TABLE inscripciones ADD COLUMN IF NOT EXISTS asistencia_usuario_id INT NULL;

UPDATE inscripciones
SET estado = COALESCE(NULLIF(estado, ''), estado_inscripcion, 'Confirmada'),
    asistencia = COALESCE(NULLIF(asistencia, ''), estado_asistencia, 'Pendiente'),
    codigo_qr = COALESCE(NULLIF(codigo_qr, ''), token_qr, datos_qr);
