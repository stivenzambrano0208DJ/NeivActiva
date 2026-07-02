<?php

namespace App\Services;

class SchemaService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function ensure() {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS participantes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                documento VARCHAR(50) NULL,
                correo VARCHAR(255) NULL,
                fecha_nacimiento DATE NULL,
                genero VARCHAR(30) NULL,
                ciudad VARCHAR(120) NULL,
                institucion VARCHAR(180) NULL,
                observaciones TEXT NULL,
                nombre_completo VARCHAR(255) NOT NULL,
                correo_electronico VARCHAR(255) NOT NULL,
                documento_identidad VARCHAR(50) NOT NULL,
                telefono VARCHAR(20) NOT NULL,
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_participante_correo (correo_electronico),
                UNIQUE KEY uq_participante_documento (documento_identidad)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                correo VARCHAR(255) NOT NULL,
                documento_identidad VARCHAR(50) NULL,
                telefono VARCHAR(20) NULL,
                password VARCHAR(255) NOT NULL,
                rol ENUM('admin', 'organizador', 'cliente', 'participante') DEFAULT 'cliente',
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_usuario_correo (correo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->agregarColumna('eventos', 'hora_evento', 'TIME NULL AFTER fecha_evento');
        $this->agregarColumna('eventos', 'organizador_id', 'INT NULL AFTER ruta_imagen');
        $this->agregarColumna('eventos', 'creado_en', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->agregarColumna('usuarios', 'documento_identidad', 'VARCHAR(50) NULL AFTER correo');
        $this->agregarColumna('usuarios', 'telefono', 'VARCHAR(20) NULL AFTER documento_identidad');
        $this->agregarColumna('usuarios', 'creado_en', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER rol');
        $this->actualizarRolesUsuario();
        $this->agregarColumna('participantes', 'usuario_id', 'INT NULL AFTER id');
        $this->agregarColumna('participantes', 'nombre', 'VARCHAR(255) NULL AFTER usuario_id');
        $this->agregarColumna('participantes', 'documento', 'VARCHAR(50) NULL AFTER nombre');
        $this->agregarColumna('participantes', 'correo', 'VARCHAR(255) NULL AFTER telefono');
        $this->agregarColumna('participantes', 'fecha_nacimiento', 'DATE NULL AFTER correo');
        $this->agregarColumna('participantes', 'genero', 'VARCHAR(30) NULL AFTER fecha_nacimiento');
        $this->agregarColumna('participantes', 'ciudad', 'VARCHAR(120) NULL AFTER genero');
        $this->agregarColumna('participantes', 'institucion', 'VARCHAR(180) NULL AFTER ciudad');
        $this->agregarColumna('participantes', 'observaciones', 'TEXT NULL AFTER institucion');
        $this->agregarColumna('participantes', 'creado_en', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->agregarColumna('participantes', 'actualizado_en', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        $this->permitirCorreoParticipanteOpcional();
        $this->agregarColumna('inscripciones', 'participante_id', 'INT NULL AFTER evento_id');
        $this->agregarColumna('inscripciones', 'usuario_id', 'INT NULL AFTER participante_id');
        $this->agregarColumna('inscripciones', 'estado_inscripcion', "ENUM('Confirmada', 'Cancelada') DEFAULT 'Confirmada' AFTER categoria_participacion");
        $this->agregarColumna('inscripciones', 'estado', "ENUM('Confirmada', 'Cancelada') DEFAULT 'Confirmada' AFTER participante_id");
        $this->agregarColumna('inscripciones', 'estado_asistencia', "ENUM('Pendiente', 'Asistio', 'Ausente') DEFAULT 'Pendiente' AFTER estado_inscripcion");
        $this->agregarColumna('inscripciones', 'asistencia', "ENUM('Pendiente', 'Asistio', 'Ausente') DEFAULT 'Pendiente' AFTER estado");
        $this->agregarColumna('inscripciones', 'datos_qr', 'VARCHAR(255) NULL AFTER estado_asistencia');
        $this->agregarColumna('inscripciones', 'token_qr', 'VARCHAR(128) NULL AFTER datos_qr');
        $this->agregarColumna('inscripciones', 'codigo_qr', 'VARCHAR(128) NULL AFTER asistencia');
        $this->agregarColumna('inscripciones', 'ruta_qr', 'VARCHAR(255) NULL AFTER token_qr');
        $this->agregarColumna('inscripciones', 'asistencia_en', 'DATETIME NULL AFTER estado_asistencia');
        $this->agregarColumna('inscripciones', 'asistencia_usuario_id', 'INT NULL AFTER asistencia_en');
        $this->agregarColumna('inscripciones', 'fecha_inscripcion', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

        $this->agregarIndice('inscripciones', 'idx_inscripciones_token_qr', 'INDEX idx_inscripciones_token_qr (token_qr)');
        $this->agregarIndice('usuarios', 'uq_usuario_documento', 'UNIQUE KEY uq_usuario_documento (documento_identidad)');
        $this->agregarIndice('participantes', 'idx_participantes_documento', 'INDEX idx_participantes_documento (documento)');
        $this->agregarIndice('participantes', 'idx_participantes_correo', 'INDEX idx_participantes_correo (correo)');
        $this->agregarIndice('participantes', 'idx_participantes_usuario', 'INDEX idx_participantes_usuario (usuario_id)');
        $this->agregarIndice('inscripciones', 'idx_inscripciones_usuario', 'INDEX idx_inscripciones_usuario (usuario_id)');
        $this->agregarIndice('inscripciones', 'uq_inscripcion_participante_evento', 'UNIQUE KEY uq_inscripcion_participante_evento (participante_id, evento_id)');
        $this->agregarLlaveForanea(
            'inscripciones',
            'fk_inscripciones_participante',
            'FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE SET NULL'
        );

        $this->sincronizarColumnasCompatibilidad();
        $this->eliminarColumnaSiExiste('participantes', 'nom' . 'bres');
        $this->eliminarColumnaSiExiste('participantes', 'apel' . 'lidos');
    }

    private function sincronizarColumnasCompatibilidad() {
        try {
            if ($this->existeTabla('participantes')) {
                $colNombreAnterior = 'nom' . 'bres';
                $colExtraAnterior = 'apel' . 'lidos';
                $nombreLegacy = $this->existeColumna('participantes', $colNombreAnterior)
                    ? $colNombreAnterior
                    : "''";
                $extraLegacy = $this->existeColumna('participantes', $colExtraAnterior)
                    ? $colExtraAnterior
                    : "''";

                $this->db->query(
                    "UPDATE participantes
                     SET documento = COALESCE(NULLIF(documento, ''), documento_identidad),
                         correo = COALESCE(NULLIF(correo, ''), correo_electronico),
                         nombre = COALESCE(
                            NULLIF(nombre, ''),
                            NULLIF(TRIM(CONCAT(COALESCE($nombreLegacy, ''), ' ', COALESCE($extraLegacy, ''))), ''),
                            nombre_completo
                         ),
                         nombre_completo = COALESCE(
                            NULLIF(nombre_completo, ''),
                            NULLIF(nombre, ''),
                            NULLIF(TRIM(CONCAT(COALESCE($nombreLegacy, ''), ' ', COALESCE($extraLegacy, ''))), '')
                         )
                     WHERE documento IS NULL OR documento = ''
                        OR correo IS NULL OR correo = ''
                        OR nombre IS NULL OR nombre = ''
                        OR nombre_completo IS NULL OR nombre_completo = ''"
                );
            }

            if ($this->existeTabla('inscripciones')) {
                $this->db->query(
                    "UPDATE inscripciones
                     SET estado = COALESCE(NULLIF(estado, ''), estado_inscripcion, 'Confirmada'),
                         asistencia = COALESCE(NULLIF(asistencia, ''), estado_asistencia, 'Pendiente'),
                         codigo_qr = COALESCE(NULLIF(codigo_qr, ''), token_qr, datos_qr)
                     WHERE estado IS NULL OR estado = ''
                        OR asistencia IS NULL OR asistencia = ''
                        OR codigo_qr IS NULL OR codigo_qr = ''"
                );
            }
        } catch (Throwable $e) {
            error_log('[SchemaService] No se pudieron sincronizar columnas de compatibilidad: ' . $e->getMessage());
        }
    }

    private function agregarColumna($tabla, $columna, $definicion) {
        if (!$this->existeTabla($tabla)) {
            return;
        }

        if ($this->existeColumna($tabla, $columna)) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE $tabla ADD COLUMN $columna $definicion");
        } catch (Throwable $e) {
            error_log('[SchemaService] No se pudo agregar columna ' . $tabla . '.' . $columna . ': ' . $e->getMessage());
        }
    }

    private function eliminarColumnaSiExiste($tabla, $columna) {
        if (!$this->existeTabla($tabla) || !$this->existeColumna($tabla, $columna)) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE $tabla DROP COLUMN $columna");
        } catch (Throwable $e) {
            error_log('[SchemaService] No se pudo eliminar columna ' . $tabla . '.' . $columna . ': ' . $e->getMessage());
        }
    }

    private function actualizarRolesUsuario() {
        if (!$this->existeTabla('usuarios')) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE usuarios MODIFY rol ENUM('admin', 'organizador', 'cliente', 'participante') DEFAULT 'cliente'");
        } catch (Throwable $e) {
            error_log('[SchemaService] No se pudo actualizar roles de usuarios: ' . $e->getMessage());
        }
    }

    private function permitirCorreoParticipanteOpcional() {
        if (!$this->existeTabla('participantes') || !$this->existeColumna('participantes', 'correo_electronico')) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE participantes MODIFY correo_electronico VARCHAR(255) NULL");
        } catch (Throwable $e) {
            error_log('[SchemaService] No se pudo hacer opcional el correo de participantes: ' . $e->getMessage());
        }
    }

    private function agregarIndice($tabla, $indice, $definicion) {
        if (!$this->existeTabla($tabla)) {
            return;
        }

        if ($this->existeIndice($tabla, $indice)) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE $tabla ADD $definicion");
        } catch (Throwable $e) {
            error_log('[SchemaService] No se pudo crear indice ' . $indice . ': ' . $e->getMessage());
        }
    }

    private function agregarLlaveForanea($tabla, $nombre, $definicion) {
        if (!$this->existeTabla($tabla)) {
            return;
        }

        if ($this->existeLlaveForanea($tabla, $nombre)) {
            return;
        }

        try {
            $this->db->query("ALTER TABLE $tabla ADD CONSTRAINT $nombre $definicion");
        } catch (Throwable $e) {
            error_log('[SchemaService] No se pudo crear llave foranea ' . $nombre . ': ' . $e->getMessage());
        }
    }

    private function existeColumna($tabla, $columna) {
        $stmt = $this->db->query(
            "SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND COLUMN_NAME = ?",
            [$tabla, $columna]
        );
        return (bool) $stmt->fetch();
    }

    private function existeTabla($tabla) {
        $stmt = $this->db->query(
            "SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             LIMIT 1",
            [$tabla]
        );

        return (bool) $stmt->fetch();
    }

    private function existeIndice($tabla, $indice) {
        $stmt = $this->db->query(
            "SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND INDEX_NAME = ?",
            [$tabla, $indice]
        );
        return (bool) $stmt->fetch();
    }

    private function existeLlaveForanea($tabla, $nombre) {
        $stmt = $this->db->query(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$tabla, $nombre]
        );

        return (bool) $stmt->fetch();
    }
}
