-- Esquema de Base de Datos para NeivActiva (Versión con Roles)
-- Nombre de la base de datos: neivactiva_db

CREATE DATABASE IF NOT EXISTS neivactiva_db;
USE neivactiva_db;

-- Tabla de Eventos
CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_evento DATE NOT NULL,
    hora_evento TIME NULL,
    ubicacion VARCHAR(255),
    categoria ENUM('Deportivo', 'Cultural', 'Educativo', 'Otro') DEFAULT 'Otro',
    estado_evento ENUM('Activo', 'Terminado') DEFAULT 'Activo',
    cupo_maximo INT NOT NULL,
    inscritos_actuales INT DEFAULT 0,
    ruta_imagen VARCHAR(255),
    organizador_id INT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Migracion para instalaciones existentes:
-- ALTER TABLE eventos ADD COLUMN hora_evento TIME NULL AFTER fecha_evento;
-- ALTER TABLE eventos ADD COLUMN organizador_id INT NULL AFTER ruta_imagen;

-- Tabla de Participantes
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
);

-- Tabla de Inscripciones
CREATE TABLE IF NOT EXISTS inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    participante_id INT NULL,
    usuario_id INT NULL,
    estado ENUM('Confirmada', 'Cancelada') DEFAULT 'Confirmada',
    asistencia ENUM('Pendiente', 'Asistio', 'Ausente') DEFAULT 'Pendiente',
    nombre_completo VARCHAR(255) NOT NULL,
    correo_electronico VARCHAR(255) NOT NULL,
    documento_identidad VARCHAR(50) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    categoria_participacion VARCHAR(50), 
    estado_inscripcion ENUM('Confirmada', 'Cancelada') DEFAULT 'Confirmada',
    estado_asistencia ENUM('Pendiente', 'Asistio', 'Ausente') DEFAULT 'Pendiente',
    asistencia_en DATETIME NULL,
    asistencia_usuario_id INT NULL,
    datos_qr VARCHAR(255),
    token_qr VARCHAR(128),
    codigo_qr VARCHAR(128),
    ruta_qr VARCHAR(255),
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inscripcion_participante_evento (participante_id, evento_id),
    KEY idx_inscripciones_token_qr (token_qr),
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE SET NULL
);

-- Tabla de Usuarios (Roles)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(255) UNIQUE NOT NULL,
    documento_identidad VARCHAR(50) NULL,
    telefono VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'organizador', 'cliente', 'participante') DEFAULT 'cliente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario_documento (documento_identidad)
);

-- El esquema queda limpio: agrega eventos, inscripciones y usuarios desde la aplicacion.

-- Migracion para asistencia QR en instalaciones existentes:
-- ALTER TABLE inscripciones ADD COLUMN asistencia_en DATETIME NULL AFTER estado_asistencia;
-- ALTER TABLE inscripciones ADD COLUMN asistencia_usuario_id INT NULL AFTER asistencia_en;
-- ALTER TABLE usuarios ADD COLUMN documento_identidad VARCHAR(50) NULL AFTER correo;
-- ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) NULL AFTER documento_identidad;
-- ALTER TABLE usuarios MODIFY rol ENUM('admin', 'organizador', 'cliente', 'participante') DEFAULT 'cliente';
-- ALTER TABLE participantes ADD COLUMN usuario_id INT NULL AFTER id;
-- ALTER TABLE inscripciones ADD COLUMN usuario_id INT NULL AFTER participante_id;
-- ALTER TABLE usuarios ADD UNIQUE KEY uq_usuario_documento (documento_identidad);
-- ALTER TABLE participantes ADD INDEX idx_participantes_usuario (usuario_id);
-- ALTER TABLE inscripciones ADD INDEX idx_inscripciones_usuario (usuario_id);
