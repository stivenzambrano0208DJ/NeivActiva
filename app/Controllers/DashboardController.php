<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\MailService;
use App\Services\CalendarService;
use App\Services\StatisticsService;
use App\Controllers\ApiController;
use Exception;
use Throwable;

class DashboardController extends Controller {
    public function dashboard() {
        $lista_eventos = $this->eventos->obtenerEventosPublicados();
        $eventos_inscritos = [];
        $participante_sesion = null;
        if (!empty($_SESSION['usuario_correo'])) {
            $eventos_inscritos = $this->inscripciones->obtenerEventosInscritosPorEmail($_SESSION['usuario_correo']);
            $participante_sesion = $this->participantes->buscarPorCorreo($_SESSION['usuario_correo']);
        }
        $metricas = [
            'eventos_activos' => count($lista_eventos),
            'total_inscritos' => array_sum(array_column($lista_eventos, 'inscritos_actuales')),
            'tasa_asistencia' => '0%',
            'certificados' => 0
        ];
        $nombre = $_SESSION['usuario_nombre'] ?? 'Invitado';
        $rol = $_SESSION['rol'] ?? 'invitado';
        $csrfToken = $this->csrfToken();
        require ROOT_PATH . '/resources/views/dashboard.php';
    }

    public function landing() {
        $eventos = $this->eventos->obtenerEventosPublicados(6);
        $resenas = (new \App\Models\ReviewModel())->listarDestacadas(3);
        require ROOT_PATH . '/resources/views/landing.php';
    }

    public function eventos() {
        $eventos = $this->eventos->obtenerEventosPublicados();
        require ROOT_PATH . '/resources/views/eventos.php';
    }

    public function calendario() {
        try {
            $calendarService = new CalendarService();
            
            // Obtener datos iniciales
            $categories = $calendarService->getCategories();
            $featuredEvents = $calendarService->getFeaturedEvents(5);
            $popularCategories = $calendarService->getPopularCategories(5);
            $stats = $calendarService->getCalendarStats();
            
            // Pasar datos a la vista
            require ROOT_PATH . '/resources/views/calendario.php';
        } catch (Exception $e) {
            error_log('[MainController] Error en calendario: ' . $e->getMessage());
            // En caso de error, mostrar vista con datos vacíos
            $categories = [];
            $featuredEvents = [];
            $popularCategories = [];
            $stats = ['total_events' => 0, 'upcoming_events' => 0, 'free_events' => 0];
            require ROOT_PATH . '/resources/views/calendario.php';
        }
    }

    public function api_calendar_events() {
        $api = new ApiController();
        $api->getCalendarEvents();
    }

    public function api_calendar_stats() {
        $api = new ApiController();
        $api->getCalendarStats();
    }

    public function api_featured_events() {
        $api = new ApiController();
        $api->getFeaturedEvents();
    }

    public function api_popular_categories() {
        $api = new ApiController();
        $api->getPopularCategories();
    }

    public function api_event_by_id($id = null) {
        if ($id !== null) {
            $_GET['id'] = $id;
        }

        $api = new ApiController();
        $api->getEventById();
    }

    public function estadisticas() {
        $this->requireRole(['admin']);
        
        $statsService = new StatisticsService();
        
        // Obtener filtros si existen
        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'event_type' => $_GET['event_type'] ?? null
        ];
        
        $metrics = $statsService->getDashboardMetrics($filters);
        
        require ROOT_PATH . '/resources/views/estadisticas.php';
    }

    public function configuracion() {
        $this->requireRole(['admin']);
        require ROOT_PATH . '/resources/views/configuracion.php';
    }
}
