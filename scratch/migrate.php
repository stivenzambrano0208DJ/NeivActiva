<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "Conectado a la base de datos con éxito.\n";

    // Verificar si las columnas ya existen
    $q = $db->query("SHOW COLUMNS FROM eventos LIKE 'formulario_campos'");
    if (!$q->fetch()) {
        $db->exec("ALTER TABLE eventos ADD COLUMN formulario_campos TEXT NULL");
        echo "Columna 'formulario_campos' agregada a tabla 'eventos'.\n";
    } else {
        echo "Columna 'formulario_campos' ya existe en 'eventos'.\n";
    }

    $q = $db->query("SHOW COLUMNS FROM inscripciones LIKE 'respuestas_campos'");
    if (!$q->fetch()) {
        $db->exec("ALTER TABLE inscripciones ADD COLUMN respuestas_campos TEXT NULL");
        echo "Columna 'respuestas_campos' agregada a tabla 'inscripciones'.\n";
    } else {
        echo "Columna 'respuestas_campos' ya existe en 'inscripciones'.\n";
    }
} catch (Exception $e) {
    echo "Error de migración: " . $e->getMessage() . "\n";
}
