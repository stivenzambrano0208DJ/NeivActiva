<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;

class UserEventsService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los eventos inscritos del usuario
     */
    public function getUserRegisteredEvents($userId, $filters = []) {
        $status = $filters['status'] ?? 'all';
        $search = $filters['search'] ?? '';
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;

        $whereConditions = ["(i.usuario_id = :user_id OR i.correo_electronico = :user_email)"];
        $params = [
            ':user_id' => $userId,
            ':user_email' => $_SESSION['usuario_correo'] ?? ''
        ];

        // Filtro por estado
        if ($status !== 'all') {
            switch ($status) {
                case 'upcoming':
                    $whereConditions[] = "(e.estado_evento = 'Activo' AND e.fecha_evento >= CURDATE())";
                    break;
                case 'completed':
                    $whereConditions[] = "(e.estado_evento = 'Terminado' OR e.fecha_evento < CURDATE())";
                    break;
                case 'cancelled':
                    $whereConditions[] = "i.estado_inscripcion = 'Cancelada'";
                    break;
                case 'certificate':
                    $whereConditions[] = "(COALESCE(i.estado_asistencia, i.asistencia, 'Pendiente') = 'Asistio' AND e.estado_evento = 'Terminado')";
                    break;
            }
        }

        // Buscador
        if ($search) {
            $whereConditions[] = "(e.titulo LIKE :search OR e.descripcion LIKE :search OR e.ubicacion LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "SELECT 
                i.id as inscripcion_id,
                i.estado_inscripcion,
                i.estado_asistencia,
                i.asistencia,
                i.fecha_inscripcion,
                i.ruta_qr,
                e.id as evento_id,
                e.titulo,
                e.descripcion,
                e.fecha_evento,
                e.hora_evento,
                e.ubicacion,
                e.categoria,
                e.cupo_maximo,
                e.inscritos_actuales,
                e.estado_evento,
                e.ruta_imagen,
                e.creado_en as evento_creado_en
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE $whereClause
                ORDER BY 
                    CASE 
                        WHEN e.estado_evento = 'Activo' AND e.fecha_evento >= CURDATE() THEN 1
                        WHEN e.estado_evento = 'Terminado' THEN 2
                        ELSE 3
                    END,
                    e.fecha_evento ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener métricas del usuario
     */
    public function getUserMetrics($userId) {
        $userEmail = $_SESSION['usuario_correo'] ?? '';
        
        $sql = "SELECT 
                COUNT(*) as total_inscritos,
                SUM(CASE WHEN (e.estado_evento = 'Terminado' OR e.fecha_evento < CURDATE()) THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN (e.estado_evento = 'Activo' AND e.fecha_evento >= CURDATE()) THEN 1 ELSE 0 END) as proximos,
                SUM(CASE WHEN (COALESCE(i.estado_asistencia, i.asistencia, 'Pendiente') = 'Asistio' AND e.estado_evento = 'Terminado') THEN 1 ELSE 0 END) as certificados_disponibles
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE (i.usuario_id = :user_id OR i.correo_electronico = :user_email)
                AND i.estado_inscripcion <> 'Cancelada'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_email' => $userEmail
        ]);
        
        return $stmt->fetch();
    }

    /**
     * Obtener timeline de actividad del usuario
     */
    public function getUserActivityTimeline($userId, $limit = 10) {
        $userEmail = $_SESSION['usuario_correo'] ?? '';
        
        $sql = "SELECT 
                'inscripcion' as tipo,
                e.titulo,
                i.fecha_inscripcion as fecha,
                'Te inscribiste en este evento' as descripcion
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE (i.usuario_id = :user_id1 OR i.correo_electronico = :user_email1)
                
                UNION ALL
                
                SELECT 
                'asistencia' as tipo,
                e.titulo,
                i.asistencia_en as fecha,
                'Asististe a este evento' as descripcion
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE (i.usuario_id = :user_id2 OR i.correo_electronico = :user_email2)
                AND COALESCE(i.estado_asistencia, i.asistencia, 'Pendiente') = 'Asistio'
                
                ORDER BY fecha DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id1' => $userId,
            ':user_email1' => $userEmail,
            ':user_id2' => $userId,
            ':user_email2' => $userEmail,
            ':limit' => (int)$limit
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Calcular porcentaje de asistencia del usuario
     */
    public function getUserAttendanceRate($userId) {
        $userEmail = $_SESSION['usuario_correo'] ?? '';
        
        $sql = "SELECT 
                (SUM(CASE WHEN COALESCE(i.estado_asistencia, i.asistencia, 'Pendiente') = 'Asistio' THEN 1 ELSE 0 END) * 100.0 / 
                 COUNT(*)) as tasa_asistencia
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE (i.usuario_id = :user_id OR i.correo_electronico = :user_email)
                AND i.estado_inscripcion <> 'Cancelada'
                AND e.estado_evento = 'Terminado'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_email' => $userEmail
        ]);
        
        $result = $stmt->fetch();
        return round($result['tasa_asistencia'] ?? 0, 1);
    }

    /**
     * Obtener eventos populares para el usuario
     */
    public function getPopularEventsForUser($userId, $limit = 3) {
        $userEmail = $_SESSION['usuario_correo'] ?? '';
        
        $sql = "SELECT 
                e.categoria,
                COUNT(*) as cantidad
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE (i.usuario_id = :user_id OR i.correo_electronico = :user_email)
                AND i.estado_inscripcion <> 'Cancelada'
                GROUP BY e.categoria
                ORDER BY cantidad DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_email' => $userEmail,
            ':limit' => (int)$limit
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Determinar el estado de un evento para mostrar en la UI
     */
    public function getEventStatus($evento) {
        $fechaEvento = strtotime($evento['fecha_evento']);
        $hoy = time();
        
        if ($evento['estado_inscripcion'] === 'Cancelada') {
            return [
                'label' => 'Cancelado',
                'class' => 'cancelled',
                'icon' => 'bi-x-circle'
            ];
        }
        
        if ($evento['estado_evento'] === 'Terminado') {
            $asistio = ($evento['estado_asistencia'] ?? $evento['asistencia'] ?? 'Pendiente') === 'Asistio';
            return [
                'label' => $asistio ? 'Completado' : 'Finalizado',
                'class' => $asistio ? 'completed' : 'finished',
                'icon' => $asistio ? 'bi-check-circle' : 'bi-flag'
            ];
        }
        
        if ($fechaEvento < $hoy) {
            return [
                'label' => 'Finalizado',
                'class' => 'finished',
                'icon' => 'bi-flag'
            ];
        }
        
        if ($fechaEvento - $hoy < 86400) { // Menos de 24 horas
            return [
                'label' => 'Próximo',
                'class' => 'upcoming-soon',
                'icon' => 'bi-clock'
            ];
        }
        
        return [
            'label' => 'Confirmado',
            'class' => 'confirmed',
            'icon' => 'bi-check-circle'
        ];
    }

    /**
     * Calcular días restantes para un evento
     */
    public function getDaysRemaining($fechaEvento) {
        $fecha = strtotime($fechaEvento);
        $hoy = time();
        $diferencia = $fecha - $hoy;
        
        if ($diferencia <= 0) {
            return 0;
        }
        
        return ceil($diferencia / 86400); // Días
    }

    /**
     * Calcular ocupación del evento
     */
    public function getOccupancyRate($inscritos, $cupo) {
        if ($cupo <= 0) return 0;
        return round(($inscritos / $cupo) * 100, 1);
    }

    /**
     * Verificar si hay certificado disponible
     */
    public function hasCertificateAvailable($evento) {
        return ($evento['estado_asistencia'] ?? $evento['asistencia'] ?? 'Pendiente') === 'Asistio' 
               && $evento['estado_evento'] === 'Terminado';
    }
}
