<?php
/**
 * Migracion de UNA SOLA VEZ: enlaza los usuarios que YA existen en NeivActiva
 * y en DJPRO antes de activar la sincronizacion automatica.
 *
 * Que hace:
 *   - Copia a DJPRO los usuarios que solo existen en NeivActiva.
 *   - Copia a NeivActiva los usuarios que solo existen en DJPRO.
 *   - Copia el HASH tal cual (ambas apps usan password_hash de PHP, es compatible),
 *     asi que la contrasena actual de cada quien sigue funcionando.
 *   - Si un correo ya existe en ambas, NO lo toca (respeta cada cuenta).
 *
 * Uso (desde la carpeta del proyecto NeivActiva):
 *   php scratch/sync_usuarios_inicial.php            (simulacion, no escribe nada)
 *   php scratch/sync_usuarios_inicial.php --apply    (aplica los cambios)
 *
 * Configuracion por variables de entorno (opcional). Por defecto asume XAMPP local:
 *   NEIV: DB_HOST/DB_NAME/DB_USER/DB_PASS         (neivactiva_db)
 *   DJPRO: DJPRO_DB_HOST/DJPRO_DB_NAME/DJPRO_DB_USER/DJPRO_DB_PASS  (djro_db)
 */

$apply = in_array('--apply', $argv, true);

function conectar(string $host, string $port, string $name, string $user, string $pass): PDO
{
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

$neivHost = getenv('DB_HOST') ?: 'localhost';
$neivPort = getenv('DB_PORT') ?: '3306';
$neivName = getenv('DB_NAME') ?: 'neivactiva_db';
$neivUser = getenv('DB_USER') ?: 'root';
$neivPass = getenv('DB_PASS'); $neivPass = ($neivPass === false) ? '' : $neivPass;

$djHost = getenv('DJPRO_DB_HOST') ?: $neivHost;
$djPort = getenv('DJPRO_DB_PORT') ?: '3306';
$djName = getenv('DJPRO_DB_NAME') ?: 'djro_db';
$djUser = getenv('DJPRO_DB_USER') ?: $neivUser;
$djPass = getenv('DJPRO_DB_PASS'); $djPass = ($djPass === false) ? $neivPass : $djPass;

echo "== Sincronizacion inicial de usuarios ==\n";
echo $apply ? "MODO: APLICAR CAMBIOS\n\n" : "MODO: SIMULACION (usa --apply para escribir)\n\n";

try {
    $neiv = conectar($neivHost, $neivPort, $neivName, $neivUser, $neivPass);
    $dj   = conectar($djHost, $djPort, $djName, $djUser, $djPass);
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a una de las bases: " . $e->getMessage() . "\n");
    exit(1);
}

// Indexar correos existentes (comparacion en minusculas).
$correosNeiv = [];
foreach ($neiv->query('SELECT LOWER(correo) c FROM usuarios')->fetchAll() as $r) {
    $correosNeiv[$r['c']] = true;
}
$correosDj = [];
foreach ($dj->query('SELECT LOWER(correo) c FROM usuarios')->fetchAll() as $r) {
    $correosDj[$r['c']] = true;
}

$insNeiv = $neiv->prepare(
    'INSERT INTO usuarios (nombre, correo, documento_identidad, telefono, password, rol)
     VALUES (?, ?, NULL, NULL, ?, "cliente")'
);
$insDj = $dj->prepare(
    'INSERT INTO usuarios (nombre, username, correo, password, rol, verificado)
     VALUES (?, NULL, ?, ?, "cliente", 1)'
);

$aDjpro = 0; $aNeiv = 0; $comunes = 0;

// NeivActiva -> DJPRO (los que faltan en DJPRO)
foreach ($neiv->query('SELECT nombre, correo, password FROM usuarios')->fetchAll() as $u) {
    $correo = strtolower(trim($u['correo']));
    if (isset($correosDj[$correo])) { $comunes++; continue; }
    echo "  NeivActiva -> DJPRO : {$correo}\n";
    if ($apply) {
        $insDj->execute([$u['nombre'], $correo, $u['password']]);
        $correosDj[$correo] = true;
    }
    $aDjpro++;
}

// DJPRO -> NeivActiva (los que faltan en NeivActiva)
foreach ($dj->query('SELECT nombre, correo, password FROM usuarios')->fetchAll() as $u) {
    $correo = strtolower(trim($u['correo']));
    if (isset($correosNeiv[$correo])) { continue; }
    echo "  DJPRO -> NeivActiva : {$correo}\n";
    if ($apply) {
        $insNeiv->execute([$u['nombre'], $correo, $u['password']]);
        $correosNeiv[$correo] = true;
    }
    $aNeiv++;
}

echo "\n== Resumen ==\n";
echo "  Ya existian en ambas (sin tocar): {$comunes}\n";
echo "  Copiados a DJPRO:      {$aDjpro}\n";
echo "  Copiados a NeivActiva: {$aNeiv}\n";
echo $apply ? "\nHecho.\n" : "\n(Simulacion) Ejecuta con --apply para aplicar.\n";
