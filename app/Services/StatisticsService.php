<?php

namespace App\Services;

use App\Core\Database;

class StatisticsService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todas las métricas del dashboard
     */
    public function getDashboardMetrics($filters = []) {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $eventType = $filters['event_type'] ?? null;

        $dateCondition = $this->buildDateCondition($startDate, $endDate);
        $eventCondition = $eventType ? "AND categoria = :event_type" : "";

        return [
            'total_events' => $this->getTotalEvents($dateCondition, $eventCondition, $eventType),
            'total_participants' => $this->getTotalParticipants($dateCondition, $eventCondition, $eventType),
            'new_users_month' => $this->getNewUsersThisMonth(),
            'retention_rate' => $this->getRetentionRate(),
            'satisfaction_level' => $this->getSatisfactionLevel(),
            'attendance_rate' => $this->getAttendanceRate($dateCondition, $eventCondition, $eventType),
            'certificates_issued' => $this->getCertificatesIssued($dateCondition, $eventCondition, $eventType),
            'popular_events' => $this->getPopularEvents($dateCondition, $eventCondition, $eventType),
            'monthly_participation' => $this->getMonthlyParticipation($dateCondition, $eventCondition, $eventType),
            'user_growth' => $this->getUserGrowth(),
            'weekly_activity' => $this->getWeeklyActivity(),
            'comparison' => $this->getComparisonWithLastMonth()
        ];
    }

    /**
     * Total de eventos creados
     */
    private function getTotalEvents($dateCondition, $eventCondition, $eventType) {
        $sql = "SELECT COUNT(*) as total FROM eventos WHERE 1=1 $dateCondition $eventCondition";
        $stmt = $this->db->prepare($sql);
        
        $params = [];
        if ($eventType) {
            $params[':event_type'] = $eventType;
        }
        if (preg_match('/:start_date/', $dateCondition)) {
            $params[':start_date'] = $GLOBALS['startDate'] ?? null;
        }
        if (preg_match('/:end_date/', $dateCondition)) {
            $params[':end_date'] = $GLOBALS['endDate'] ?? null;
        }
        
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Total de participantes únicos
     */
    private function getTotalParticipants($dateCondition, $eventCondition, $eventType) {
        $sql = "SELECT COUNT(DISTINCT participante_id) as total 
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE 1=1 $dateCondition $eventCondition";
        $stmt = $this->db->prepare($sql);
        
        $params = [];
        if ($eventType) {
            $params[':event_type'] = $eventType;
        }
        
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Usuarios nuevos este mes
     */
    private function getNewUsersThisMonth() {
        $sql = "SELECT COUNT(*) as total FROM usuarios 
                WHERE YEAR(creado_en) = YEAR(CURRENT_DATE)
                AND MONTH(creado_en) = MONTH(CURRENT_DATE)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    /**
     * Tasa de retención (usuarios que participaron en más de un evento)
     */
    private function getRetentionRate() {
        $sql = "SELECT 
                (COUNT(DISTINCT CASE WHEN event_count > 1 THEN participante_id END) * 100.0 / 
                 COUNT(DISTINCT participante_id)) as rate
                FROM (
                    SELECT participante_id, COUNT(*) as event_count
                    FROM inscripciones
                    WHERE estado_inscripcion = 'Confirmada'
                    GROUP BY participante_id
                ) as participant_events";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return round($result['rate'] ?? 0, 1);
    }

    /**
     * Nivel de satisfacción (basado en asistencia y feedback)
     */
    private function getSatisfactionLevel() {
        // Calcular basado en tasa de asistencia como proxy de satisfacción
        $sql = "SELECT 
                (SUM(CASE WHEN estado_asistencia = 'Asistio' THEN 1 ELSE 0 END) * 100.0 / 
                 COUNT(*)) as rate
                FROM inscripciones 
                WHERE estado_asistencia IN ('Asistio', 'Ausente')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return round($result['rate'] ?? 0, 1);
    }

    /**
     * Porcentaje de asistencia
     */
    private function getAttendanceRate($dateCondition, $eventCondition, $eventType) {
        $sql = "SELECT 
                (SUM(CASE WHEN estado_asistencia = 'Asistio' THEN 1 ELSE 0 END) * 100.0 / 
                 COUNT(*)) as rate
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE 1=1 $dateCondition $eventCondition";
        $stmt = $this->db->prepare($sql);
        
        $params = [];
        if ($eventType) {
            $params[':event_type'] = $eventType;
        }
        
        $stmt->execute($params);
        $result = $stmt->fetch();
        return round($result['rate'] ?? 0, 1);
    }

    /**
     * Certificados emitidos
     */
    private function getCertificatesIssued($dateCondition, $eventCondition, $eventType) {
        $sql = "SELECT COUNT(*) as total
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE estado_asistencia = 'Asistio' 
                AND e.estado_evento = 'Terminado'
                $dateCondition $eventCondition";
        $stmt = $this->db->prepare($sql);
        
        $params = [];
        if ($eventType) {
            $params[':event_type'] = $eventType;
        }
        
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Eventos más populares
     */
    private function getPopularEvents($dateCondition, $eventCondition, $eventType) {
        $sql = "SELECT e.id, e.titulo, e.categoria, 
                COUNT(i.id) as asistentes,
                e.cupo_maximo
                FROM eventos e
                LEFT JOIN inscripciones i ON e.id = i.evento_id AND i.estado_inscripcion = 'Confirmada'
                WHERE 1=1 $dateCondition $eventCondition
                GROUP BY e.id, e.titulo, e.categoria, e.cupo_maximo
                ORDER BY asistentes DESC
                LIMIT 5";
        $stmt = $this->db->prepare($sql);
        
        $params = [];
        if ($eventType) {
            $params[':event_type'] = $eventType;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Participación mensual (últimos 6 meses)
     */
    private function getMonthlyParticipation($dateCondition, $eventCondition, $eventType) {
        $sql = "SELECT 
                DATE_FORMAT(fecha_inscripcion, '%Y-%m') as mes,
                COUNT(*) as participantes
                FROM inscripciones i
                JOIN eventos e ON i.evento_id = e.id
                WHERE fecha_inscripcion >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                $eventCondition
                GROUP BY DATE_FORMAT(fecha_inscripcion, '%Y-%m')
                ORDER BY mes ASC";
        $stmt = $this->db->prepare($sql);
        
        $params = [];
        if ($eventType) {
            $params[':event_type'] = $eventType;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Crecimiento de usuarios (últimos 6 meses)
     */
    private function getUserGrowth() {
        $sql = "SELECT 
                DATE_FORMAT(creado_en, '%Y-%m') as mes,
                COUNT(*) as usuarios
                FROM usuarios
                WHERE creado_en >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(creado_en, '%Y-%m')
                ORDER BY mes ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Actividad semanal (inscripciones por día de la semana)
     */
    private function getWeeklyActivity() {
        $sql = "SELECT 
                DAYNAME(fecha_inscripcion) as dia,
                COUNT(*) as cantidad
                FROM inscripciones
                WHERE fecha_inscripcion >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                GROUP BY DAYNAME(fecha_inscripcion)
                ORDER BY FIELD(DAYNAME(fecha_inscripcion), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Comparación con el mes anterior
     */
    private function getComparisonWithLastMonth() {
        $currentMonth = $this->getMonthlyStats(date('Y-m'), date('Y-m'));
        $lastMonth = $this->getMonthlyStats(
            date('Y-m', strtotime('first day of previous month')),
            date('Y-m', strtotime('last day of previous month'))
        );

        return [
            'participants_growth' => $this->calculateGrowth($currentMonth['participants'], $lastMonth['participants']),
            'events_growth' => $this->calculateGrowth($currentMonth['events'], $lastMonth['events']),
            'users_growth' => $this->calculateGrowth($currentMonth['users'], $lastMonth['users'])
        ];
    }

    private function getMonthlyStats($start, $end) {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM eventos WHERE DATE_FORMAT(creado_en, '%Y-%m') = :month1) as events,
                (SELECT COUNT(*) FROM inscripciones WHERE DATE_FORMAT(fecha_inscripcion, '%Y-%m') = :month2) as participants,
                (SELECT COUNT(*) FROM usuarios WHERE DATE_FORMAT(creado_en, '%Y-%m') = :month3) as users";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':month1' => $start,
            ':month2' => $start,
            ':month3' => $start
        ]);
        return $stmt->fetch();
    }

    private function calculateGrowth($current, $previous) {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function buildDateCondition($startDate, $endDate) {
        if ($startDate && $endDate) {
            $GLOBALS['startDate'] = $startDate;
            $GLOBALS['endDate'] = $endDate;
            return "AND DATE(creado_en) BETWEEN :start_date AND :end_date";
        }
        return "";
    }
}
