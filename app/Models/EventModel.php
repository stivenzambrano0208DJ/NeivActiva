<?php

namespace App\Models;

class EventModel extends BaseModel {
    
    public function __construct() {
        parent::__construct('eventos');
    }

    public function obtenerEventos() {
        return $this->all('fecha_evento ASC, hora_evento ASC');
    }

    public function obtenerEventosPublicados($limite = null) {
        $sql = "SELECT * FROM eventos
                WHERE estado_evento = 'Activo'
                ORDER BY fecha_evento ASC, hora_evento ASC";

        if ($limite !== null) {
            $sql .= " LIMIT " . max(1, (int) $limite);
        }

        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerEventosDisponibles() {
        return $this->db->query("SELECT * FROM eventos
                WHERE estado_evento = 'Activo'
                AND inscritos_actuales < cupo_maximo
                ORDER BY fecha_evento ASC, hora_evento ASC")->fetchAll();
    }

    public function obtenerDisponiblePorId($eventoId) {
        return $this->db->query("SELECT * FROM eventos
                WHERE id = ?
                AND estado_evento = 'Activo'
                AND inscritos_actuales < cupo_maximo
                LIMIT 1", [$eventoId])->fetch();
    }

    public function crear($datos) {
        $sql = "INSERT INTO eventos (titulo, descripcion, fecha_evento, hora_evento, ubicacion, categoria, cupo_maximo, ruta_imagen, organizador_id, formulario_campos)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->db->query($sql, [
            $datos['titulo'],
            $datos['descripcion'],
            $datos['fecha_evento'],
            $datos['hora_evento'],
            $datos['ubicacion'],
            $datos['categoria'],
            $datos['cupo_maximo'],
            $datos['ruta_imagen'],
            $datos['organizador_id'] ?? null,
            $datos['formulario_campos'] ?? null
        ]);
    }

    public function actualizar($eventoId, $datos) {
        $sql = "UPDATE eventos
                SET titulo = ?,
                    descripcion = ?,
                    fecha_evento = ?,
                    hora_evento = ?,
                    ubicacion = ?,
                    categoria = ?,
                    cupo_maximo = ?,
                    formulario_campos = ?";
        $params = [
            $datos['titulo'],
            $datos['descripcion'],
            $datos['fecha_evento'],
            $datos['hora_evento'],
            $datos['ubicacion'],
            $datos['categoria'],
            $datos['cupo_maximo'],
            $datos['formulario_campos'] ?? null
        ];

        if (!empty($datos['ruta_imagen'])) {
            $sql .= ", ruta_imagen = ?";
            $params[] = $datos['ruta_imagen'];
        }

        $sql .= " WHERE id = ?";
        $params[] = $eventoId;

        return $this->db->query($sql, $params);
    }

    public function usuarioPuedeEditar($eventoId, $rol, $usuarioId) {
        if ($rol === 'admin') {
            return true;
        }

        if ($rol !== 'organizador' || !$usuarioId) {
            return false;
        }

        $evento = $this->find($eventoId);
        return $evento && (int) ($evento['organizador_id'] ?? 0) === (int) $usuarioId;
    }

    public function actualizarCupo($eventoId) {
        $stmt = $this->db->query(
            "UPDATE eventos
             SET inscritos_actuales = inscritos_actuales + 1
             WHERE id = ? AND inscritos_actuales < cupo_maximo",
            [$eventoId]
        );

        return $stmt->rowCount() > 0;
    }

    public function marcarTerminado($eventoId) {
        return $this->db->query("UPDATE eventos SET estado_evento = 'Terminado' WHERE id = ?", [$eventoId]);
    }
}
