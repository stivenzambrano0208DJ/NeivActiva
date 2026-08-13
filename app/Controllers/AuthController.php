<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\MailService;
use App\Services\CalendarService;
use App\Controllers\ApiController;
use Exception;
use Throwable;

class AuthController extends Controller {
    public function login() {
        $csrfToken = $this->csrfToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf()) {
                $this->redirect('/login?error=csrf');
            }

            if ($this->loginRateLimitActivo()) {
                $this->redirect('/login?error=limite');
            }

            $identificador = $this->limpiarTexto($_POST['identificador'] ?? '');
            $password = (string) ($_POST['password'] ?? '');

            if ($identificador === '' || $password === '') {
                $this->registrarIntentoLoginFallido();
                $this->redirect('/login?error=campos');
            }

            $u = $this->usuarios->login($identificador, $password);
            if ($u) {
                session_regenerate_id(true);
                $this->limpiarIntentosLogin();
                $_SESSION['usuario_id'] = $u['id'];
                $_SESSION['usuario_nombre'] = $u['nombre'];
                $_SESSION['usuario_correo'] = $u['correo'];
                $_SESSION['rol'] = $u['rol'];
                // Version de sesion para invalidacion por cambio de contrasena.
                $_SESSION['token_version'] = (int) ($u['token_version'] ?? 0);

                $this->redirect($this->redireccionPorRol($u['rol']));
            } else {
                $this->registrarIntentoLoginFallido();
                $this->redirect('/login?error=1');
            }
        }
        require ROOT_PATH . '/resources/views/login.php';
    }

    public function registro() {
        $csrfToken = $this->csrfToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf()) {
                $this->redirect('/register?error=csrf');
            }

            $datos = [
                'nombre' => $this->limpiarTexto($_POST['nombre'] ?? ''),
                'correo' => strtolower($this->limpiarTexto($_POST['correo'] ?? '')),
                'documento_identidad' => $this->limpiarTexto($_POST['documento_identidad'] ?? ''),
                'tipo_documento' => \App\Core\Validator::tipoDocumento($_POST['tipo_documento'] ?? 'CC'),
                'telefono' => $this->limpiarTexto($_POST['telefono'] ?? ''),
                'password' => (string) ($_POST['password'] ?? ''),
            ];
            $confirmacion = (string) ($_POST['password_confirmacion'] ?? '');

            if ($datos['nombre'] === ''
                || $datos['correo'] === ''
                || $datos['documento_identidad'] === ''
                || $datos['telefono'] === ''
                || $datos['password'] === '') {
                $this->redirect('/register?error=campos');
            }

            if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
                $this->redirect('/register?error=correo');
            }

            if (!\App\Core\Validator::esNombreValido($datos['nombre'])) {
                $this->redirect('/register?error=nombre_invalido');
            }

            if (!\App\Core\Validator::esDocumentoNumerico($datos['documento_identidad'])) {
                $this->redirect('/register?error=documento_invalido');
            }

            if (strlen($datos['password']) < 8) {
                $this->redirect('/register?error=password');
            }

            if (!hash_equals($datos['password'], $confirmacion)) {
                $this->redirect('/register?error=confirmacion');
            }

            if ($this->usuarios->existeCorreo($datos['correo'])) {
                $this->redirect('/register?error=existe');
            }

            if ($this->usuarios->existeDocumento($datos['documento_identidad'])) {
                $this->redirect('/register?error=documento');
            }

            $usuarioId = $this->usuarios->registrar($datos);
            if ($usuarioId) {
                // Sincronizar la cuenta con DJPRO (mismo correo+contrasena en ambas
                // plataformas). No debe bloquear el registro si DJPRO no responde.
                try {
                    (new \App\Services\AccountSyncService())
                        ->alRegistrar($datos['nombre'], $datos['correo'], $datos['password']);
                } catch (Throwable $e) {
                    error_log('Registro: fallo al sincronizar con DJPRO: ' . $e->getMessage());
                }

                // Notificación de bienvenida por correo (no debe bloquear el registro).
                try {
                    (new MailService())->enviarBienvenidaRegistro($datos);
                } catch (Throwable $e) {
                    error_log('Registro: fallo al enviar correo de bienvenida: ' . $e->getMessage());
                }
                $this->redirect('/login?msg=cuenta_creada');
            } else {
                $this->redirect('/register?error=1');
            }
        }
        require ROOT_PATH . '/resources/views/registro.php';
    }

    public function logout() {
        \App\Core\Auth::logout();
        $this->redirect('/');
    }

    /**
     * Solicitud de recuperación: el usuario ingresa su correo y se le
     * envía un enlace con token. Respuesta genérica (anti-enumeración).
     */
    public function forgotPassword() {
        $csrfToken = $this->csrfToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf()) {
                $this->redirect('/forgot-password?error=csrf');
            }

            $correo = strtolower($this->limpiarTexto($_POST['correo'] ?? ''));
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->redirect('/forgot-password?error=correo');
            }

            try {
                $resultado = $this->usuarios->generarTokenReset($correo);
                if ($resultado) {
                    $appUrl = rtrim($_ENV['APP_URL'] ?? (defined('APP_URL') ? APP_URL : 'http://localhost/NeivActiva'), '/');
                    $resetUrl = $appUrl . '/reset-password?token=' . $resultado['token'];
                    (new MailService())->enviarEnlaceRecuperacion(
                        $correo,
                        $resultado['usuario']['nombre'] ?? '',
                        $resetUrl
                    );
                }
            } catch (Throwable $e) {
                error_log('ForgotPassword: ' . $e->getMessage());
            }

            // Mismo mensaje exista o no el correo.
            $this->redirect('/forgot-password?msg=enviado');
        }

        require ROOT_PATH . '/resources/views/forgot_password.php';
    }

    /**
     * Restablecimiento: valida el token y permite definir la nueva contraseña.
     */
    public function resetPassword() {
        $csrfToken = $this->csrfToken();
        $token = $this->limpiarTexto($_GET['token'] ?? ($_POST['token'] ?? ''));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf()) {
                $this->redirect('/reset-password?token=' . urlencode($token) . '&error=csrf');
            }

            $password = (string) ($_POST['password'] ?? '');
            $confirmacion = (string) ($_POST['password_confirmacion'] ?? '');

            if (strlen($password) < 8) {
                $this->redirect('/reset-password?token=' . urlencode($token) . '&error=password');
            }
            if (!hash_equals($password, $confirmacion)) {
                $this->redirect('/reset-password?token=' . urlencode($token) . '&error=confirmacion');
            }

            // Capturamos el correo del token ANTES de restablecer (el token queda
            // marcado como usado y ya no se puede consultar despues).
            $filaToken = $this->usuarios->buscarTokenResetValido($token);

            if ($this->usuarios->restablecerPasswordConToken($token, $password)) {
                // Propagar el cambio de contrasena a DJPRO para mantenerlas iguales.
                if ($filaToken && !empty($filaToken['correo'])) {
                    try {
                        (new \App\Services\AccountSyncService())
                            ->alCambiarPassword($filaToken['correo'], $password);
                    } catch (Throwable $e) {
                        error_log('Reset: fallo al sincronizar con DJPRO: ' . $e->getMessage());
                    }
                }
                $this->redirect('/login?msg=password_actualizada');
            }
            $this->redirect('/reset-password?token=' . urlencode($token) . '&error=token');
        }

        // GET: ¿el token sigue siendo válido?
        $tokenValido = $token !== '' && $this->usuarios->buscarTokenResetValido($token) !== null;
        require ROOT_PATH . '/resources/views/reset_password.php';
    }
}
