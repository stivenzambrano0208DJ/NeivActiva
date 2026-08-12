<?php
/**
 * NeivActiva - Front Controller
 */

use Dotenv\Dotenv;
use App\Core\Router;

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
ini_set('default_charset', 'utf-8');

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Autoload (Composer PSR-4)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
} else {
    die("Falta el autoloader de Composer. Ejecuta 'composer install'.");
}

// Cargar variables de entorno
if (file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Cargar configuración legacy (compatibilidad con código antiguo)
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require ROOT_PATH . '/config/config.php';
}

// Reporte de errores
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Auto-provisionar/normalizar el esquema de base de datos (columnas e índices nuevos)
try {
    (new App\Services\SchemaService())->ensureOnce();
} catch (Throwable $e) {
    error_log('[SchemaService] No se pudo verificar el esquema: ' . $e->getMessage());
}

// ──────────────────────────────────────────────────────────────
// MODO COMPATIBILIDAD: si viene ?view=X, resolver como ruta limpia sin perder POST.
// ──────────────────────────────────────────────────────────────
if (isset($_GET['view'])) {
    $mapeoVistas = [
        'landing'                => '/',
        'login'                  => '/login',
        'registro'               => '/register',
        'register'               => '/register',
        'logout'                 => '/logout',
        'forgot_password'        => '/forgot-password',
        'reset_password'         => '/reset-password',
        'dashboard'              => '/dashboard',
        'calendario'             => '/calendario',
        'inscripcion'            => '/inscripcion',
        'mis_qr'                 => '/mis-qr',
        'mis_certificados'       => '/mis-certificados',
        'mis_eventos_inscritos'  => '/mis-eventos',
        'gestionar_eventos'      => '/admin/eventos',
        'gestionar_inscripciones'=> '/admin/inscripciones',
        'asistencia'             => '/admin/asistencia',
        'participantes'          => '/admin/participantes',
        'inscripciones_admin'    => '/admin/inscripciones-directas',
        'carga_masiva'           => '/admin/carga-masiva',
        'usuarios'               => '/admin/usuarios',
        'estadisticas'           => '/admin/estadisticas',
        'configuracion'          => '/admin/configuracion',
        'detalle_evento'         => '/evento',
        'ver_qr'                 => '/qr/ver',
        'descargar_qr'           => '/qr/descargar',
        'enviar_qr'              => '/qr/enviar',
        'inscribir_evento_ajax'  => '/ajax/inscribir',
        'enviar_qr_ajax'         => '/ajax/enviar-qr',
        'verificar_participante_cuenta' => '/ajax/verificar-participante',
        'buscar_por_qr'          => '/ajax/buscar-qr',
        'descargar_certificado'  => '/certificado/descargar',
        'enviar_certificado'     => '/certificado/enviar',
        'descargar_plantilla_participantes' => '/admin/plantilla-participantes',
        'api_calendar_events'    => '/api/calendar-events',
        'api_calendar_stats'     => '/api/calendar-stats',
        'api_featured_events'    => '/api/featured-events',
        'api_popular_categories' => '/api/popular-categories',
        'api_event_by_id'        => '/api/event',
    ];

    $view = $_GET['view'];
    if (isset($mapeoVistas[$view])) {
        // Construir query string sin el parámetro view
        $params = $_GET;
        unset($params['view']);
        $qs = http_build_query($params);
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
        $_SERVER['REQUEST_URI'] = $scriptDir . $mapeoVistas[$view] . ($qs ? '?' . $qs : '');
    }
}

// ──────────────────────────────────────────────────────────────
// ROUTER
// ──────────────────────────────────────────────────────────────
$router = new Router();

// --- Autenticación ---
$router->get('/login',    ['App\Controllers\AuthController', 'login']);
$router->post('/login',   ['App\Controllers\AuthController', 'login']);
$router->get('/register', ['App\Controllers\AuthController', 'registro']);
$router->post('/register',['App\Controllers\AuthController', 'registro']);
$router->get('/logout',   ['App\Controllers\AuthController', 'logout']);
$router->get('/forgot-password',  ['App\Controllers\AuthController', 'forgotPassword']);
$router->post('/forgot-password', ['App\Controllers\AuthController', 'forgotPassword']);
$router->get('/reset-password',   ['App\Controllers\AuthController', 'resetPassword']);
$router->post('/reset-password',  ['App\Controllers\AuthController', 'resetPassword']);

// --- Páginas públicas ---
$router->get('/',          ['App\Controllers\DashboardController', 'landing']);
$router->get('/eventos',   ['App\Controllers\DashboardController', 'eventos']);
$router->get('/calendario',['App\Controllers\DashboardController', 'calendario']);

// --- Usuario autenticado ---
$router->get('/dashboard',         ['App\Controllers\DashboardController', 'dashboard']);
$router->get('/inscripcion',        ['App\Controllers\EventController', 'inscripcion']);
$router->post('/inscripcion',       ['App\Controllers\EventController', 'inscripcion']);
$router->get('/mis-qr',             ['App\Controllers\EventController', 'mis_qr']);
$router->get('/mis-certificados',   ['App\Controllers\EventController', 'mis_certificados']);
$router->get('/mis-eventos',        ['App\Controllers\EventController', 'mis_eventos_inscritos']);

// --- Evento detalle ---
$router->get('/evento',             ['App\Controllers\EventController', 'detalle_evento']);
$router->get('/evento/{id}',        ['App\Controllers\EventController', 'detalle_evento']);

// --- QR ---
$router->get('/qr/ver',             ['App\Controllers\EventController', 'ver_qr']);
$router->get('/qr/descargar',       ['App\Controllers\EventController', 'descargar_qr']);
$router->post('/qr/enviar',         ['App\Controllers\EventController', 'enviar_qr']);

// --- AJAX / API ---
$router->post('/ajax/inscribir',       ['App\Controllers\EventController', 'inscribir_evento_ajax']);
$router->post('/ajax/enviar-qr',       ['App\Controllers\EventController', 'enviar_qr_ajax']);
$router->post('/ajax/verificar-participante', ['App\Controllers\UserController', 'verificar_participante_cuenta']);
$router->post('/ajax/buscar-qr',       ['App\Controllers\EventController', 'buscar_por_qr']);

// --- Certificados ---
$router->get('/certificado/descargar', ['App\Controllers\EventController', 'descargar_certificado']);
$router->post('/certificado/enviar',   ['App\Controllers\EventController', 'enviar_certificado']);

// --- Admin ---
$router->get('/admin/eventos',                 ['App\Controllers\EventController',  'gestionar_eventos']);
$router->post('/admin/eventos',                ['App\Controllers\EventController',  'gestionar_eventos']);
$router->get('/admin/inscripciones',           ['App\Controllers\EventController',  'gestionar_inscripciones']);
$router->post('/admin/inscripciones',          ['App\Controllers\EventController',  'gestionar_inscripciones']);
$router->get('/admin/asistencia',              ['App\Controllers\EventController',  'asistencia']);
$router->post('/admin/asistencia',             ['App\Controllers\EventController',  'asistencia']);
$router->get('/admin/participantes',           ['App\Controllers\UserController',   'participantes']);
$router->post('/admin/participantes',          ['App\Controllers\UserController',   'participantes']);
$router->get('/admin/inscripciones-directas',  ['App\Controllers\UserController',   'inscripciones_admin']);
$router->post('/admin/inscripciones-directas', ['App\Controllers\UserController',   'inscripciones_admin']);
$router->get('/admin/carga-masiva',            ['App\Controllers\UserController',   'carga_masiva']);
$router->post('/admin/carga-masiva',           ['App\Controllers\UserController',   'carga_masiva']);
$router->get('/admin/usuarios',                ['App\Controllers\UserController',   'usuarios']);
$router->post('/admin/usuarios',               ['App\Controllers\UserController',   'usuarios']);
$router->get('/admin/estadisticas',            ['App\Controllers\DashboardController', 'estadisticas']);
$router->get('/admin/configuracion',           ['App\Controllers\DashboardController', 'configuracion']);

// --- Descargas Admin ---
$router->get('/admin/plantilla-participantes', ['App\Controllers\UserController', 'descargar_plantilla_participantes']);

// API del calendario
$router->get('/api/calendar-events',     ['App\Controllers\DashboardController', 'api_calendar_events']);
$router->get('/api/calendar-stats',      ['App\Controllers\DashboardController', 'api_calendar_stats']);
$router->get('/api/featured-events',     ['App\Controllers\DashboardController', 'api_featured_events']);
$router->get('/api/popular-categories',  ['App\Controllers\DashboardController', 'api_popular_categories']);
$router->get('/api/event',               ['App\Controllers\DashboardController', 'api_event_by_id']);
$router->get('/api/event/{id}',          ['App\Controllers\DashboardController', 'api_event_by_id']);

$router->resolve();
