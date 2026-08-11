<?php

namespace App\Services;

use App\Core\Database;
use Exception;
use PDO;
use PDOException;

class CalendarService {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log('[CalendarService] Error en conexión: ' . $e->getMessage());
            throw new Exception('No se pudo conectar a la base de datos');
        }
    }

    /**
     * Obtener eventos para el calendario con filtros
     */
    public function getCalendarEvents($filters = []) {
        try {
            $category = $filters['category'] ?? null;
            $startDate = $filters['start_date'] ?? null;
            $endDate = $filters['end_date'] ?? null;
            $freeOnly = $filters['free_only'] ?? false;
            $upcomingOnly = $filters['upcoming_only'] ?? false;
            $search = $filters['search'] ?? '';

            $whereConditions = ["e.estado_evento = 'Activo'"];
            $params = [];

            // Filtro por categoría
            if ($category) {
                $whereConditions[] = "e.categoria = :category";
                $params[':category'] = $category;
            }

            // Filtro por rango de fechas
            if ($startDate && $endDate) {
                $whereConditions[] = "e.fecha_evento BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $startDate;
                $params[':end_date'] = $endDate;
            }

            // Filtro eventos gratuitos (todos son gratuitos por defecto)
            if ($freeOnly) {
                // Sin condición extra ya que no hay columna costo
            }

            // Filtro próximos eventos
            if ($upcomingOnly) {
                $whereConditions[] = "e.fecha_evento >= CURDATE()";
            }

            // Buscador
            if ($search) {
                $whereConditions[] = "(e.titulo LIKE :search OR e.descripcion LIKE :search OR e.ubicacion LIKE :search)";
                $params[':search'] = "%$search%";
            }

            $whereClause = implode(' AND ', $whereConditions);

            $sql = "SELECT 
                    e.id,
                    e.titulo,
                    e.descripcion,
                    e.fecha_evento,
                    e.hora_evento,
                    e.ubicacion,
                    e.categoria,
                    e.cupo_maximo,
                    e.inscritos_actuales,
                    e.ruta_imagen,
                    e.estado_evento,
                    e.creado_en
                    FROM eventos e
                    WHERE $whereClause
                    ORDER BY e.fecha_evento ASC, e.hora_evento ASC";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear eventos para FullCalendar
            return array_map(function($event) {
                $startDateTime = $event['fecha_evento'] . ($event['hora_evento'] ? 'T' . $event['hora_evento'] : '');
                $endDateTime = $event['fecha_evento'] . ($event['hora_evento'] ? 'T' . $event['hora_evento'] : '');
                
                // Si hay hora, agregar 2 horas como duración estimada
                if ($event['hora_evento']) {
                    $endDateTime = date('Y-m-d\TH:i:s', strtotime($startDateTime . ' +2 hours'));
                }

                return [
                    'id' => $event['id'],
                    'title' => $event['titulo'],
                    'start' => $startDateTime,
                    'end' => $endDateTime,
                    'backgroundColor' => $this->getCategoryColor($event['categoria']),
                    'borderColor' => $this->getCategoryColor($event['categoria']),
                    'extendedProps' => [
                        'description' => $event['descripcion'] ?? '',
                        'location' => $event['ubicacion'] ?? '',
                        'category' => $event['categoria'] ?? '',
                        'capacity' => $event['cupo_maximo'] ?? 0,
                        'cost' => 0,
                        'image' => $event['ruta_imagen'] ? (strpos($event['ruta_imagen'], '/') === 0 ? $event['ruta_imagen'] : '/' . ltrim($event['ruta_imagen'], '/')) : '/assets/images/event-placeholder.jpg',
                        'isFull' => ($event['inscritos_actuales'] ?? 0) >= ($event['cupo_maximo'] ?? 0),
                        'isFree' => true
                    ]
                ];
            }, $events);
        } catch (PDOException $e) {
            error_log('[CalendarService] Error en getCalendarEvents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener categorías disponibles
     */
    public function getCategories() {
        try {
            $sql = "SELECT DISTINCT categoria FROM eventos WHERE estado_evento = 'Activo' ORDER BY categoria";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($result, 'categoria');
        } catch (PDOException $e) {
            error_log('[CalendarService] Error en getCategories: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener próximos eventos destacados
     */
    public function getFeaturedEvents($limit = 5) {
        try {
            $sql = "SELECT 
                    e.id,
                    e.titulo,
                    e.fecha_evento,
                    e.hora_evento,
                    e.ubicacion,
                    e.categoria,
                    e.cupo_maximo,
                    e.inscritos_actuales,
                    e.ruta_imagen
                    FROM eventos e
                    WHERE e.estado_evento = 'Activo'
                    AND e.fecha_evento >= CURDATE()
                    ORDER BY e.inscritos_actuales DESC, e.fecha_evento ASC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[CalendarService] Error en getFeaturedEvents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener eventos por categoría popular
     */
    public function getPopularCategories($limit = 5) {
        try {
            $sql = "SELECT 
                    e.categoria,
                    COUNT(*) as event_count,
                    SUM(e.inscritos_actuales) as total_registrations
                    FROM eventos e
                    WHERE e.estado_evento = 'Activo'
                    AND e.fecha_evento >= CURDATE()
                    GROUP BY e.categoria
                    ORDER BY total_registrations DESC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[CalendarService] Error en getPopularCategories: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener color por categoría
     */
    private function getCategoryColor($category) {
        $colors = [
            'Deportivo' => '#3b82f6',
            'Cultural' => '#ec4899',
            'Educativo' => '#10b981',
            'Otro' => '#6b7280'
        ];

        return $colors[$category] ?? '#f5b400';
    }

    /**
     * Obtener evento por ID
     */
    public function getEventById($eventId) {
        try {
            $sql = "SELECT 
                    e.id,
                    e.titulo,
                    e.descripcion,
                    e.fecha_evento,
                    e.hora_evento,
                    e.ubicacion,
                    e.categoria,
                    e.cupo_maximo,
                    e.inscritos_actuales,
                    e.ruta_imagen,
                    e.estado_evento,
                    e.organizador_id
                    FROM eventos e
                    WHERE e.id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => (int)$eventId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[CalendarService] Error en getEventById: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener estadísticas del calendario
     */
    public function getCalendarStats() {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN fecha_evento >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events,
                    SUM(CASE WHEN fecha_evento < CURDATE() THEN 1 ELSE 0 END) as past_events,
                    COUNT(*) as free_events,
                    SUM(inscritos_actuales) as total_registrations
                    FROM eventos
                    WHERE estado_evento = 'Activo'";

            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[CalendarService] Error en getCalendarStats: ' . $e->getMessage());
            return [
                'total_events' => 0,
                'upcoming_events' => 0,
                'past_events' => 0,
                'free_events' => 0,
                'total_registrations' => 0
            ];
        }
    }
}
