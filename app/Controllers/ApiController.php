<?php

namespace App\Controllers;

use App\Services\CalendarService;
use Exception;

class ApiController {
    private $calendarService;

    public function __construct() {
        $this->calendarService = new CalendarService();
    }

    /**
     * API para obtener eventos del calendario
     */
    public function getCalendarEvents() {
        header('Content-Type: application/json');
        
        try {
            $filters = [
                'category' => $_GET['category'] ?? null,
                'start_date' => $_GET['start'] ?? null,
                'end_date' => $_GET['end'] ?? null,
                'free_only' => isset($_GET['free_only']),
                'upcoming_only' => isset($_GET['upcoming_only']),
                'search' => $_GET['search'] ?? ''
            ];

            $events = $this->calendarService->getCalendarEvents($filters);
            echo json_encode(['success' => true, 'events' => $events]);
        } catch (Exception $e) {
            error_log('[ApiController] Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * API para obtener estadísticas
     */
    public function getCalendarStats() {
        header('Content-Type: application/json');
        
        try {
            $stats = $this->calendarService->getCalendarStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            error_log('[ApiController] Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * API para obtener eventos destacados
     */
    public function getFeaturedEvents() {
        header('Content-Type: application/json');
        
        try {
            $limit = $_GET['limit'] ?? 5;
            $events = $this->calendarService->getFeaturedEvents($limit);
            echo json_encode(['success' => true, 'events' => $events]);
        } catch (Exception $e) {
            error_log('[ApiController] Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * API para obtener categorías populares
     */
    public function getPopularCategories() {
        header('Content-Type: application/json');
        
        try {
            $limit = $_GET['limit'] ?? 5;
            $categories = $this->calendarService->getPopularCategories($limit);
            echo json_encode(['success' => true, 'categories' => $categories]);
        } catch (Exception $e) {
            error_log('[ApiController] Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * API para obtener evento por ID
     */
    public function getEventById() {
        header('Content-Type: application/json');
        
        try {
            $eventId = $_GET['id'] ?? 0;
            $event = $this->calendarService->getEventById($eventId);
            
            if ($event) {
                echo json_encode(['success' => true, 'event' => $event]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Evento no encontrado']);
            }
        } catch (Exception $e) {
            error_log('[ApiController] Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}
