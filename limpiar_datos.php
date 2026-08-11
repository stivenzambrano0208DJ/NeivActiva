<?php
define('ROOT_PATH', __DIR__);
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$db = App\Core\Database::getInstance()->getConnection();

try {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    $db->exec("DELETE FROM inscripciones");
    $ins = $db->query("SELECT ROW_COUNT()")->fetchColumn();

    $db->exec("DELETE FROM participantes");
    $part = $db->query("SELECT ROW_COUNT()")->fetchColumn();

    $db->exec("DELETE FROM eventos");
    $ev = $db->query("SELECT ROW_COUNT()")->fetchColumn();

    $db->exec("ALTER TABLE inscripciones AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE participantes   AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE eventos         AUTO_INCREMENT = 1");

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "OK: inscripciones=$ins participantes=$part eventos=$ev usuarios=intactos" . PHP_EOL;

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
?>
