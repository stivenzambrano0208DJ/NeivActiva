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
    /**
     * Lee una variable de entorno de forma fiable: primero getenv() (lo que
     * inyecta Docker/Dokploy) y si no, $_ENV (lo que carga phpdotenv en local).
     */
    private function env(string $clave, ?string $porDefecto = null): ?string
    {
        $v = getenv($clave);
        if ($v !== false && $v !== '') {
            return $v;
        }
        if (isset($_ENV[$clave]) && $_ENV[$clave] !== '') {
            return (string) $_ENV[$clave];
        }
        return $porDefecto;
    }

    private function conexionDjpro(): PDO
    {
        $host = $this->env('DJPRO_DB_HOST', $this->env('DB_HOST', 'localhost'));
        $port = $this->env('DJPRO_DB_PORT', '3306');
        $name = $this->env('DJPRO_DB_NAME', 'djro_db');
        $user = $this->env('DJPRO_DB_USER', $this->env('DB_USER', 'root'));
        $pass = $this->env('DJPRO_DB_PASS', $this->env('DB_PASS', ''));

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
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

            // Invalidar las sesiones abiertas de esa cuenta en DJPRO. En su propio
            // try/catch por si la columna token_version aun no existe alla.
            try {
                $pdo->prepare('UPDATE usuarios SET token_version = token_version + 1 WHERE correo = ?')
                    ->execute([$correo]);
            } catch (Throwable $e) {
                error_log('[AccountSync->DJPRO] token_version: ' . $e->getMessage());
            }
        } catch (Throwable $e) {
            error_log('[AccountSync->DJPRO] alCambiarPassword: ' . $e->getMessage());
        }
    }
}
