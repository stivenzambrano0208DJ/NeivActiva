<?php
/**
 * NeivActiva - Instalador Final
 *
 * Deshabilitado por defecto para evitar operaciones criticas desde una ruta publica.
 * Habilitar temporalmente con WEB_SETUP_ENABLED=true y WEB_SETUP_TOKEN en .env.
 */

use Dotenv\Dotenv;

define('ROOT_PATH', dirname(__DIR__));

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

if (class_exists(Dotenv::class) && file_exists(ROOT_PATH . '/.env')) {
    Dotenv::createImmutable(ROOT_PATH)->safeLoad();
}

require_once ROOT_PATH . '/config/config.php';

$webSetupEnabled = filter_var($_ENV['WEB_SETUP_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);
$setupToken = (string) ($_ENV['WEB_SETUP_TOKEN'] ?? '');
$requestToken = (string) ($_GET['token'] ?? '');

if (PHP_SAPI !== 'cli' && (!$webSetupEnabled || $setupToken === '' || !hash_equals($setupToken, $requestToken))) {
    http_response_code(404);
    exit('No encontrado.');
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', DB_NAME)) {
    http_response_code(500);
    exit('Nombre de base de datos invalido.');
}

echo "<h1>Instalador de NeivActiva</h1>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Configurando base de datos... ";
    $dbName = '`' . str_replace('`', '``', DB_NAME) . '`';
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE $dbName");
    echo "✅<br>";

    echo "Creando tablas... ";
    $sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    $pdo->exec($sql);
    echo "✅ OK<br>";

    echo "<br><strong style='color: green;'>¡Sistema configurado correctamente!</strong><br>";
    echo "<p>Base de datos lista, sin datos de prueba y conservando usuarios existentes.</p>";
    echo "<a href='index.php'>Ir a NeivActiva</a>";

} catch (Exception $e) {
    error_log('[Setup] ' . $e->getMessage());
    echo "<br><strong style='color: red;'>Error:</strong> No se pudo completar la instalacion.";
}
