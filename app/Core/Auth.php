<?php

namespace App\Core;

class Auth {
    /**
     * Verifica si el usuario tiene el rol requerido
     */
    public static function requireRole($allowedRoles) {
        $userRole = $_SESSION['rol'] ?? 'invitado';
        if ($userRole === 'participante' && in_array('cliente', $allowedRoles, true)) {
            $userRole = 'cliente';
        }
        if (!in_array($userRole, $allowedRoles, true)) {
            if (!isset($_SESSION['usuario_id'])) {
                self::redirect('/login');
            } else {
                self::redirect('/dashboard?error=NoTienesPermiso');
            }
        }
    }

    /**
     * Verifica si el usuario está autenticado
     */
    public static function check() {
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Obtiene el rol del usuario actual
     */
    public static function role() {
        return $_SESSION['rol'] ?? 'invitado';
    }

    /**
     * Obtiene el ID del usuario actual
     */
    public static function id() {
        return $_SESSION['usuario_id'] ?? null;
    }

    /**
     * Redirección segura
     */
    public static function redirect($url) {
        $appUrl = rtrim($_ENV['APP_URL'] ?? (defined('APP_URL') ? APP_URL : 'http://localhost/NeivActiva'), '/');
        $url = '/' . ltrim((string) $url, '/');
        header("Location: " . $appUrl . ($url === '/' ? '/' : $url));
        exit;
    }

    /**
     * Rate limiting para login
     */
    public static function loginRateLimitActivo() {
        $intentos = $_SESSION['login_intentos'] ?? ['count' => 0, 'time' => time()];
        if (time() - (int) ($intentos['time'] ?? 0) > 900) {
            $_SESSION['login_intentos'] = ['count' => 0, 'time' => time()];
            return false;
        }
        return (int) ($intentos['count'] ?? 0) >= 8;
    }

    /**
     * Registrar intento de login fallido
     */
    public static function registrarIntentoLoginFallido() {
        $intentos = $_SESSION['login_intentos'] ?? ['count' => 0, 'time' => time()];
        if (time() - (int) ($intentos['time'] ?? 0) > 900) {
            $intentos = ['count' => 0, 'time' => time()];
        }
        $intentos['count'] = (int) ($intentos['count'] ?? 0) + 1;
        $_SESSION['login_intentos'] = $intentos;
    }

    /**
     * Limpiar intentos de login
     */
    public static function limpiarIntentosLogin() {
        unset($_SESSION['login_intentos']);
    }

    public static function logout() {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    /**
     * Redirección por rol
     */
    public static function redireccionPorRol($rol) {
        if ($rol === 'admin') {
            return '/dashboard';
        }
        if ($rol === 'organizador') {
            return '/admin/eventos';
        }
        if ($rol === 'participante' || $rol === 'cliente') {
            return '/dashboard';
        }
        return '/';
    }
}
