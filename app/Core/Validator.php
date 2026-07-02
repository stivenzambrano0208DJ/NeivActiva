<?php

namespace App\Core;

class Validator {
    /**
     * Valida datos de participante
     */
    public static function validateParticipant($datos) {
        foreach (['nombre_completo', 'documento_identidad'] as $campo) {
            if ($datos[$campo] === '') {
                return 'obligatorios';
            }
        }

        if ($datos['correo_electronico'] !== '' && !filter_var($datos['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            return 'correo';
        }

        if (strlen($datos['nombre_completo']) < 3 || strlen($datos['documento_identidad']) < 4) {
            return 'obligatorios';
        }

        return null;
    }

    /**
     * Valida categoría de participación
     */
    public static function validateParticipationCategory($categoria) {
        $categoria = Helper::cleanText($categoria);
        return in_array($categoria, ['Juvenil', 'Adulto', 'Senior'], true) ? $categoria : 'Adulto';
    }

    /**
     * Valida contraseña de cuenta de participante
     */
    public static function validateParticipantAccountPassword($password, $confirmacion, $correo, $documento) {
        if (strlen($password) < 8) {
            return 'password_corta';
        }

        if (!hash_equals($password, $confirmacion)) {
            return 'password_confirmacion';
        }

        // Verificar si el correo ya existe (debe hacerse en el modelo)
        // Verificar si el documento ya existe (debe hacerse en el modelo)

        return null;
    }

    /**
     * Sanitiza entrada de usuario
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida que un valor no esté vacío
     */
    public static function required($value) {
        return !empty(trim($value));
    }

    /**
     * Valida longitud mínima
     */
    public static function minLength($value, $min) {
        return strlen($value) >= $min;
    }

    /**
     * Valida longitud máxima
     */
    public static function maxLength($value, $max) {
        return strlen($value) <= $max;
    }
}
