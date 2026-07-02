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
}
