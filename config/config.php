<?php
/**
 * NeivActiva - Configuration
 * 
 * Este archivo es LEGACY. Usar variables de entorno (.env) en su lugar.
 * Mantenido solo para compatibilidad con código antiguo.
 */

// Database Settings (LEGACY - usar .env)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'neivactiva_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// App Settings (LEGACY - usar .env)
define('APP_NAME', $_ENV['APP_NAME'] ?? 'NeivActiva');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/NeivActiva');

// SMTP Settings (LEGACY - usar .env)
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? 'tls');

// Error Reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
