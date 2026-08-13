<?php

namespace App\Core;

class Helper {
    /**
     * Limpia texto: recorta espacios y elimina emojis (no permitidos en
     * ningun campo). Es el punto unico por donde pasan casi todas las
     * entradas de la app (via Controller::limpiarTexto).
     */
    public static function cleanText($value) {
        return trim(self::stripEmojis((string) $value));
    }

    /**
     * Elimina emojis y selectores de variacion/ZWJ de una cadena.
     */
    public static function stripEmojis($value) {
        $value = (string) $value;
        $limpio = preg_replace(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{2300}-\x{23FF}\x{FE00}-\x{FE0F}\x{200D}\x{1F1E6}-\x{1F1FF}]/u',
            '',
            $value
        );
        // preg_replace devuelve null si falla (p.ej. cadena no UTF-8 valida).
        return $limpio === null ? $value : $limpio;
    }

    /**
     * Convierte texto para PDF (maneja caracteres especiales)
     */
    public static function textForPdf($texto) {
        $texto = (string) $texto;
        if (function_exists('iconv')) {
            $convertido = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
            if ($convertido !== false) {
                $texto = $convertido;
            }
        }
        return str_replace(["\\", "(", ")", "\r", "\n"], ["\\\\", "\\(", "\\)", " ", " "], $texto);
    }

    /**
     * Log de errores
     */
    public static function logError($mensaje, $contexto = []) {
        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;
        if (!empty($contexto)) {
            $linea .= ' ' . json_encode($contexto, JSON_UNESCAPED_UNICODE);
        }
        error_log($linea);
    }

    /**
     * Genera URL de ruta
     */
    public static function routeUrl($path = '/') {
        $appUrl = rtrim($_ENV['APP_URL'] ?? (defined('APP_URL') ? APP_URL : 'http://localhost/NeivActiva'), '/');
        $path = '/' . ltrim((string) $path, '/');
        return $appUrl . ($path === '/' ? '/' : $path);
    }

    /**
     * Redirección
     */
    public static function redirect($url) {
        header("Location: " . self::routeUrl($url));
        exit;
    }

    /**
     * Respuesta JSON
     */
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
