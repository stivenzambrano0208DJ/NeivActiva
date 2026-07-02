<?php

namespace App\Core;

class Csrf {
    /**
     * Genera y retorna el token CSRF
     */
    public static function token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida el token CSRF de una petición POST
     */
    public static function validate() {
        $token = $_POST['csrf_token'] ?? '';
        return $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    /**
     * Genera el campo HTML CSRF
     */
    public static function field() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
