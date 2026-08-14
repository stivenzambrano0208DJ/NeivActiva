<?php

namespace App\Models;

class ReviewModel extends BaseModel {

    public function __construct() {
        parent::__construct('resenas');
    }

    /**
     * Devuelve el id de una inscripcion que HABILITA a la persona a reseñar el
     * evento: debe haber asistido (estado_asistencia = 'Asistio'), el evento debe
     * estar terminado (o su fecha ya paso) y no debe existir una resena previa
     * de esa inscripcion. Retorna null si no es elegible.
     */
    public function inscripcionReseniable($eventoId, $correo, $usuarioId = null) {
        $correo = strtolower(trim((string) $correo));
        $usuarioId = $usuarioId ? (int) $usuarioId : 0;

        $sql = "SELECT i.id
                FROM inscripciones i
                JOIN eventos e ON e.id = i.evento_id
                WHERE i.evento_id = ?
                  AND (i.correo_electronico = ? OR (i.usuario_id IS NOT NULL AND i.usuario_id = ?))
                  AND COALESCE(i.estado_asistencia, i.asistencia, 'Pendiente') = 'Asistio'
                  AND (e.estado_evento = 'Terminado' OR e.fecha_evento < CURDATE())
                  AND NOT EXISTS (SELECT 1 FROM resenas r WHERE r.inscripcion_id = i.id)
                LIMIT 1";

        $fila = $this->db->query($sql, [(int) $eventoId, $correo, $usuarioId])->fetch();
        return $fila ? (int) $fila['id'] : null;
    }

    /** Inserta una resena. */
    public function crear($datos) {
        $calificacion = max(1, min(5, (int) ($datos['calificacion'] ?? 5)));
        $this->db->query(
            "INSERT INTO resenas (evento_id, inscripcion_id, usuario_id, nombre, rol_texto, calificacion, comentario)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                (int) $datos['evento_id'],
                !empty($datos['inscripcion_id']) ? (int) $datos['inscripcion_id'] : null,
                !empty($datos['usuario_id']) ? (int) $datos['usuario_id'] : null,
                trim((string) ($datos['nombre'] ?? 'Participante')),
                trim((string) ($datos['rol_texto'] ?? '')) ?: null,
                $calificacion,
                trim((string) ($datos['comentario'] ?? '')),
            ]
        );

        return (int) $this->db->getConnection()->lastInsertId();
    }

    /** Resenas aprobadas de un evento, mas recientes primero. */
    public function obtenerPorEvento($eventoId) {
        return $this->db->query(
            "SELECT * FROM resenas
             WHERE evento_id = ? AND aprobada = 1
             ORDER BY creado_en DESC",
            [(int) $eventoId]
        )->fetchAll();
    }

    /** Promedio y total de resenas de un evento. */
    public function resumenEvento($eventoId) {
        $fila = $this->db->query(
            "SELECT COUNT(*) total, ROUND(AVG(calificacion), 1) promedio
             FROM resenas WHERE evento_id = ? AND aprobada = 1",
            [(int) $eventoId]
        )->fetch();

        return [
            'total' => (int) ($fila['total'] ?? 0),
            'promedio' => (float) ($fila['promedio'] ?? 0),
        ];
    }

    /**
     * Resenas destacadas para la landing: aprobadas, positivas (>= 4 estrellas)
     * y con comentario, mas recientes primero.
     */
    public function listarDestacadas($limite = 3) {
        $limite = max(1, (int) $limite);
        return $this->db->query(
            "SELECT r.*, e.titulo AS evento_titulo
             FROM resenas r
             JOIN eventos e ON e.id = r.evento_id
             WHERE r.aprobada = 1 AND r.calificacion >= 4 AND TRIM(r.comentario) <> ''
             ORDER BY r.creado_en DESC
             LIMIT {$limite}"
        )->fetchAll();
    }
}
