<?php

namespace App\Models;

class RegistrationModel extends BaseModel {

    public function __construct() {
        parent::__construct('inscripciones');
    }

    public function registrar($datos) {
        $sql = "INSERT INTO inscripciones (
                    evento_id,
                    participante_id,
                    usuario_id,
                    nombre_completo,
                    correo_electronico,
                    documento_identidad,
                    telefono,
                    categoria_participacion,
                    estado_inscripcion,
                    datos_qr,
                    token_qr
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $datos['evento_id'],
            $datos['participante_id'],
            $datos['usuario_id'] ?? null,
            $datos['nombre_completo'],
            $datos['correo_electronico'],
            $datos['documento_identidad'],
            $datos['telefono'],
            $datos['categoria_participacion'],
            $datos['estado_inscripcion'] ?? 'Confirmada',
            $datos['token_qr'],
            $datos['token_qr']
        ]);

        return (int) $this->db->getConnection()->lastInsertId();
    }

    public function registrarParticipanteEvento($participante, $eventoId, $codigoQr = null) {
        $codigoQr = $codigoQr ?: bin2hex(random_bytes(32));
        $nombreCompleto = (string) ($participante['nombre'] ?? $participante['nombre_completo'] ?? '');

        $correo = (string) ($participante['correo'] ?? $participante['correo_electronico'] ?? '');
        $documento = (string) ($participante['documento'] ?? $participante['documento_identidad'] ?? '');

        return $this->create([
            'evento_id' => (int) $eventoId,
            'participante_id' => (int) $participante['id'],
            'usuario_id' => null,
            'nombre_completo' => $nombreCompleto,
            'correo_electronico' => $correo,
            'documento_identidad' => $documento,
            'telefono' => $participante['telefono'] ?? '',
            'categoria_participacion' => 'General',
            'estado_inscripcion' => 'Confirmada',
            'estado' => 'Confirmada',
            'estado_asistencia' => 'Pendiente',
            'asistencia' => 'Pendiente',
            'datos_qr' => $codigoQr,
            'token_qr' => $codigoQr,
            'codigo_qr' => $codigoQr,
        ]);
    }

    public function existeParticipanteEnEvento($participanteId, $eventoId) {
        return (bool) $this->db->query(
            "SELECT id FROM {$this->table}
             WHERE participante_id = ?
               AND evento_id = ?
               AND COALESCE(estado_inscripcion, estado, 'Confirmada') <> 'Cancelada'
             LIMIT 1",
            [(int) $participanteId, (int) $eventoId]
        )->fetch();
    }

    public function existeInscripcionActiva($participanteId, $eventoId) {
        $sql = "SELECT id FROM inscripciones
                WHERE participante_id = ?
                AND evento_id = ?
                AND estado_inscripcion <> 'Cancelada'
                LIMIT 1";

        return (bool) $this->db->query($sql, [$participanteId, $eventoId])->fetch();
    }

    public function existeInscripcionActivaPorEmail($email, $eventoId) {
        $sql = "SELECT id FROM inscripciones
                WHERE correo_electronico = ?
                AND evento_id = ?
                AND estado_inscripcion <> 'Cancelada'
                LIMIT 1";

        return (bool) $this->db->query($sql, [$email, $eventoId])->fetch();
    }

    public function obtenerEventosInscritosPorEmail($email) {
        $sql = "SELECT evento_id FROM inscripciones
                WHERE correo_electronico = ?
                AND estado_inscripcion <> 'Cancelada'";

        return array_map('intval', array_column($this->db->query($sql, [$email])->fetchAll(), 'evento_id'));
    }

    public function guardarQr($inscripcionId, $rutaQr) {
        return $this->db->query(
            "UPDATE inscripciones SET ruta_qr = ? WHERE id = ?",
            [$rutaQr, $inscripcionId]
        );
    }

    public function guardarTokenQr($inscripcionId, $tokenQr) {
        $sets = [];
        $params = [];

        foreach (['datos_qr', 'token_qr', 'codigo_qr'] as $columna) {
            if ($this->columnExists($columna)) {
                $sets[] = "$columna = ?";
                $params[] = $tokenQr;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = (int) $inscripcionId;
        return $this->db->query("UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?", $params);
    }

    public function obtenerPorId($id) {
        $sql = "SELECT i.*, e.titulo as evento_titulo, e.fecha_evento, e.estado_evento, e.ubicacion
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE i.id = ?";

        return $this->db->query($sql, [$id])->fetch();
    }

    public function obtenerAsistentesParaCertificado($eventoId) {
        $sql = "SELECT i.*, e.titulo as evento_titulo, e.fecha_evento, e.estado_evento, e.ubicacion
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE i.evento_id = ?
                  AND COALESCE(i.estado_asistencia, i.asistencia, 'Pendiente') = 'Asistio'
                  AND i.correo_electronico IS NOT NULL
                  AND i.correo_electronico <> ''";

        return $this->db->query($sql, [(int) $eventoId])->fetchAll();
    }

    public function obtenerRecientesAdmin($limite = 500) {
        $limite = max(1, (int) $limite);
        return $this->db->query(
            "SELECT i.*,
                    e.titulo AS evento_titulo,
                    e.fecha_evento,
                    e.hora_evento,
                    e.estado_evento,
                    p.nombre,
                    p.documento,
                    p.correo,
                    p.ciudad,
                    p.institucion
             FROM {$this->table} i
             LEFT JOIN eventos e ON i.evento_id = e.id
             LEFT JOIN participantes p ON i.participante_id = p.id
             ORDER BY i.fecha_inscripcion DESC
             LIMIT $limite"
        )->fetchAll();
    }

    public function obtenerRecientes($limite = 10) {
        $limite = (int) $limite;
        return $this->db->query("SELECT i.*, e.titulo as evento_titulo, e.fecha_evento, e.hora_evento, e.estado_evento
                FROM inscripciones i
                LEFT JOIN eventos e ON i.evento_id = e.id
                ORDER BY COALESCE(i.asistencia_en, i.fecha_inscripcion) DESC
                LIMIT $limite")->fetchAll();
    }

    public function obtenerPorEmail($email) {
        $sql = "SELECT i.*, e.titulo as evento_titulo, e.fecha_evento, e.estado_evento 
                FROM inscripciones i 
                JOIN eventos e ON i.evento_id = e.id 
                WHERE i.correo_electronico = ? 
                ORDER BY e.fecha_evento DESC";
        return $this->db->query($sql, [$email])->fetchAll();
    }

    public function obtenerCertificadoPorId($id) {
        $sql = "SELECT i.*, e.titulo as evento_titulo, e.fecha_evento, e.estado_evento, e.ubicacion
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE i.id = ?";
        return $this->db->query($sql, [$id])->fetch();
    }

    public function buscarPorQr($codigoQr) {
        $token = $this->extraerTokenQr($codigoQr);
        $where = [];
        $params = [];

        foreach (['token_qr', 'datos_qr', 'codigo_qr'] as $columna) {
            if ($this->columnExists($columna)) {
                $where[] = "i.$columna = ?";
                $params[] = $token;
            }
        }

        if (empty($where)) {
            return false;
        }

        $sql = "SELECT i.*, e.titulo as evento_titulo, e.fecha_evento, e.hora_evento, e.estado_evento, e.ubicacion
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE " . implode(' OR ', $where);
        return $this->db->query($sql, $params)->fetch();
    }

    public function registrarAsistenciaQr($inscripcionId, $usuarioId) {
        $sets = ["asistencia_en = NOW()"];
        $params = [];

        if ($this->columnExists('estado_asistencia')) {
            $sets[] = "estado_asistencia = 'Asistio'";
        }

        if ($this->columnExists('asistencia')) {
            $sets[] = "asistencia = 'Asistio'";
        }

        if ($this->columnExists('asistencia_usuario_id')) {
            $sets[] = "asistencia_usuario_id = ?";
            $params[] = (int) $usuarioId;
        }

        $params[] = (int) $inscripcionId;
        $stmt = $this->db->query(
            "UPDATE {$this->table}
             SET " . implode(', ', $sets) . "
             WHERE id = ?
               AND COALESCE(estado_inscripcion, estado, 'Confirmada') = 'Confirmada'
               AND COALESCE(estado_asistencia, asistencia, 'Pendiente') <> 'Asistio'",
            $params
        );

        return $stmt->rowCount() > 0;
    }

    public function actualizarAsistencia($inscripcionId, $estado, $usuarioId = null) {
        $estadosPermitidos = ['Pendiente', 'Asistio', 'Ausente'];
        if (!in_array($estado, $estadosPermitidos, true)) {
            return false;
        }

        $sets = [];
        $params = [];

        foreach (['estado_asistencia', 'asistencia'] as $columna) {
            if ($this->columnExists($columna)) {
                $sets[] = "$columna = ?";
                $params[] = $estado;
            }
        }

        if ($estado === 'Asistio') {
            $sets[] = "asistencia_en = COALESCE(asistencia_en, NOW())";
            if ($this->columnExists('asistencia_usuario_id')) {
                $sets[] = "asistencia_usuario_id = COALESCE(asistencia_usuario_id, ?)";
                $params[] = $usuarioId;
            }
        } else {
            $sets[] = "asistencia_en = NULL";
            if ($this->columnExists('asistencia_usuario_id')) {
                $sets[] = "asistencia_usuario_id = NULL";
            }
        }

        $params[] = (int) $inscripcionId;
        return $this->db->query(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?",
            $params
        );
    }

    private function extraerTokenQr($codigoQr) {
        $codigoQr = trim($codigoQr);
        $payload = json_decode($codigoQr, true);

        if (is_array($payload) && isset($payload['token'])) {
            return trim((string) $payload['token']);
        }

        return $codigoQr;
    }
}
