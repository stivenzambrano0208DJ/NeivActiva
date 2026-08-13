<?php

namespace App\Services;

use PDO;
use Throwable;

/**
 * Sincronizacion de cuentas con la plataforma hermana DJPRO.
 *
 * NeivActiva y DJPRO son dos aplicaciones con bases de datos SEPARADAS, pero
 * queremos que el mismo correo + contrasena sirva para iniciar sesion en ambas.
 * Como las dos cifran con password_hash()/password_verify() de PHP, basta con
 * replicar la cuenta (o su contrasena) en la base de datos de la otra app.
 *
 * Regla de oro: esto NUNCA debe romper el flujo de NeivActiva. Si DJPRO no esta
 * accesible, se registra el error en el log y se continua con normalidad.
 *
 * Configuracion (opcional) via variables de entorno; por defecto asume que ambas
 * bases viven en el mismo servidor MySQL con las mismas credenciales:
 *   DJPRO_DB_HOST, DJPRO_DB_NAME, DJPRO_DB_USER, DJPRO_DB_PASS
 */
class AccountSyncService
{
    private function conexionDjpro(): PDO
    {
        $host = $_ENV['DJPRO_DB_HOST'] ?? ($_ENV['DB_HOST'] ?? 'localhost');
        $name = $_ENV['DJPRO_DB_NAME'] ?? 'djro_db';
        $user = $_ENV['DJPRO_DB_USER'] ?? ($_ENV['DB_USER'] ?? 'root');
        $pass = $_ENV['DJPRO_DB_PASS'] ?? ($_ENV['DB_PASS'] ?? '');

        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Crea la cuenta espejo en DJPRO si el correo aun no existe alli.
     * Si ya existe una cuenta con ese correo en DJPRO, se respeta (no se toca
     * su contrasena) para no interferir con cuentas previas.
     */
    public function alRegistrar(string $nombre, string $correo, string $passwordPlano): void
    {
        $correo = strtolower(trim($correo));
        if ($correo === '' || $passwordPlano === '') {
            return;
        }

        try {
            $pdo = $this->conexionDjpro();

            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
            $stmt->execute([$correo]);
            if ($stmt->fetch()) {
                return; // Ya existe en DJPRO: no lo tocamos.
            }

            $ins = $pdo->prepare(
                'INSERT INTO usuarios (nombre, username, correo, password, rol, verificado)
                 VALUES (?, NULL, ?, ?, "cliente", 1)'
            );
            $ins->execute([
                trim($nombre),
                $correo,
                password_hash($passwordPlano, PASSWORD_DEFAULT),
            ]);
        } catch (Throwable $e) {
            error_log('[AccountSync->DJPRO] alRegistrar: ' . $e->getMessage());
        }
    }

    /**
     * Propaga un cambio de contrasena a DJPRO (solo si el correo existe alli).
     * Asi, resetear la clave en NeivActiva mantiene "la misma contrasena" en DJPRO.
     */
    public function alCambiarPassword(string $correo, string $passwordPlano): void
    {
        $correo = strtolower(trim($correo));
        if ($correo === '' || $passwordPlano === '') {
            return;
        }

        try {
            $pdo = $this->conexionDjpro();
            $upd = $pdo->prepare('UPDATE usuarios SET password = ? WHERE correo = ?');
            $upd->execute([
                password_hash($passwordPlano, PASSWORD_DEFAULT),
                $correo,
            ]);
        } catch (Throwable $e) {
            error_log('[AccountSync->DJPRO] alCambiarPassword: ' . $e->getMessage());
        }
    }
}
