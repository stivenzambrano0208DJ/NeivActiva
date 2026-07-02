<?php

namespace App\Core;

use App\Models\EventModel;
use App\Models\RegistrationModel;
use App\Models\UsuarioModel;
use App\Models\ParticipantModel;
use App\Services\QrCodeService;
use App\Services\MailService;
use App\Core\Database;
use App\Core\View;
use Exception;
use Throwable;
use RuntimeException;
use InvalidArgumentException;

class Controller {
    protected $eventos;
    protected $inscripciones;
    protected $usuarios;
    protected $participantes;
    protected $qrService;

    protected function render($view, $data = []) {
        return View::render($view, $data);
    }

    protected function routeUrl($path = '/') {
        return Helper::routeUrl($path);
    }

    protected function redirect($url) {
        Helper::redirect($url);
    }

    protected function json($data, $statusCode = 200) {
        Helper::json($data, $statusCode);
    }

    public function __construct() {
        $this->eventos = new EventModel();
        $this->inscripciones = new RegistrationModel();
        $this->usuarios = new UsuarioModel();
        $this->participantes = new ParticipantModel();
        $this->qrService = new QrCodeService();
    }

    protected function requireRole($allowedRoles) {
        Auth::requireRole($allowedRoles);
    }

    protected function csrfToken() {
        return Csrf::token();
    }

    protected function validarCsrf() {
        return Csrf::validate();
    }

    protected function limpiarTexto($valor) {
        return Helper::cleanText($valor);
    }

    protected function datosParticipanteDesdePost() {
        return [
            'nombre_completo' => $this->limpiarTexto($_POST['nombre_completo'] ?? ''),
            'correo_electronico' => strtolower($this->limpiarTexto($_POST['correo_electronico'] ?? '')),
            'documento_identidad' => $this->limpiarTexto($_POST['documento_identidad'] ?? ''),
            'telefono' => $this->limpiarTexto($_POST['telefono'] ?? ''),
        ];
    }

    protected function validarParticipante($datos) {
        return Validator::validateParticipant($datos);
    }

    protected function validarCategoriaParticipacion($categoria) {
        return Validator::validateParticipationCategory($categoria);
    }

    protected function puedeGestionarInscripciones() {
        return in_array($_SESSION['rol'] ?? 'invitado', ['admin', 'organizador'], true);
    }

    protected function loginRateLimitActivo() {
        return Auth::loginRateLimitActivo();
    }

    protected function registrarIntentoLoginFallido() {
        Auth::registrarIntentoLoginFallido();
    }

    protected function limpiarIntentosLogin() {
        Auth::limpiarIntentosLogin();
    }

    protected function redireccionPorRol($rol) {
        return Auth::redireccionPorRol($rol);
    }

    protected function textoPdf($texto) {
        return Helper::textForPdf($texto);
    }

    protected function logError($mensaje, $contexto = []) {
        Helper::logError($mensaje, $contexto);
    }

    protected function obtenerCertificadoDisponible($inscripcionId) {
        $certificado = $this->inscripciones->obtenerCertificadoPorId($inscripcionId);
        if (!$certificado) {
            $this->redirect('/mis-certificados?error=no_encontrado');
        }

        $rol = $_SESSION['rol'] ?? 'invitado';
        $correoSesion = $_SESSION['usuario_correo'] ?? '';
        $esPropietario = $correoSesion === $certificado['correo_electronico'];
        $puedeGestionar = in_array($rol, ['admin', 'organizador'], true);
        $eventoFinalizado = ($certificado['estado_evento'] ?? 'Activo') === 'Terminado' || strtotime($certificado['fecha_evento']) < time();
        $asistio = $certificado['estado_asistencia'] === 'Asistio';

        if ((!$esPropietario && !$puedeGestionar) || !$eventoFinalizado || !$asistio) {
            $this->redirect('/mis-certificados?error=no_disponible');
        }

        return $certificado;
    }

    protected function validarCuentaParticipanteDesdePost($datosParticipante) {
        if (!$this->puedeGestionarInscripciones()) {
            return null;
        }

        $password = (string) ($_POST['password_participante'] ?? '');
        $confirmacion = (string) ($_POST['password_participante_confirmacion'] ?? '');

        $error = Validator::validateParticipantAccountPassword($password, $confirmacion, $datosParticipante['correo_electronico'], $datosParticipante['documento_identidad']);
        if ($error) {
            return $error;
        }

        if ($this->usuarios->existeCorreo($datosParticipante['correo_electronico'])) {
            return 'usuario_correo';
        }

        if ($this->usuarios->existeDocumento($datosParticipante['documento_identidad'])) {
            return 'usuario_documento';
        }

        return null;
    }

    protected function cuentaCoincideConParticipante($usuario, $datosParticipante) {
        if (!$usuario) {
            return false;
        }

        $mismoCorreo = strtolower((string) $usuario['correo']) === strtolower((string) $datosParticipante['correo_electronico']);
        $documentoUsuario = trim((string) ($usuario['documento_identidad'] ?? ''));
        $mismoDocumento = $documentoUsuario === ''
            || $documentoUsuario === (string) $datosParticipante['documento_identidad'];

        return $mismoCorreo && $mismoDocumento;
    }

    protected function enviarCorreoBienvenidaParticipante($datos, $passwordPlano) {
        $mailService = new MailService();
        return $mailService->enviarCorreoBienvenida($datos, $passwordPlano);
    }

    protected function obtenerInscripcionAutorizada($inscripcionId, $token = '') {
        $inscripcion = $this->inscripciones->obtenerPorId($inscripcionId);
        if (!$inscripcion) {
            return null;
        }

        $esPropietario = ($_SESSION['usuario_correo'] ?? '') === $inscripcion['correo_electronico'];
        $tokenValido = $token !== '' && hash_equals((string) ($inscripcion['token_qr'] ?? $inscripcion['datos_qr']), $token);

        if (!$esPropietario && !$this->puedeGestionarInscripciones() && !$tokenValido) {
            return null;
        }

        return $inscripcion;
    }

    protected function crearPayloadQr($inscripcion) {
        return json_encode([
            'inscripcion_id' => (int) $inscripcion['id'],
            'participante' => $inscripcion['nombre_completo'],
            'evento_id' => (int) $inscripcion['evento_id'],
            'token' => $inscripcion['token_qr'] ?? $inscripcion['datos_qr'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function enviarCorreoQr($inscripcion) {
        $mailService = new MailService();
        return $mailService->enviarCorreoQr($inscripcion);
    }

    protected function asegurarQrInscripcion($inscripcion) {
        $token = $inscripcion['token_qr'] ?? $inscripcion['datos_qr'] ?? '';
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $this->inscripciones->guardarTokenQr((int) $inscripcion['id'], $token);
            $inscripcion['token_qr'] = $token;
            $inscripcion['datos_qr'] = $token;
        }

        $rutaFisica = !empty($inscripcion['ruta_qr']) ? ROOT_PATH . $inscripcion['ruta_qr'] : '';
        if ($rutaFisica === '' || !file_exists($rutaFisica)) {
            $nombreQr = 'qr_inscripcion_' . (int) $inscripcion['id'] . '_' . substr(hash('sha256', $token), 0, 16) . '.png';
            $rutaRelativaQr = '/public/uploads/qrs/' . $nombreQr;
            $this->qrService->generarPng($this->crearPayloadQr($inscripcion), ROOT_PATH . $rutaRelativaQr);
            $this->inscripciones->guardarQr((int) $inscripcion['id'], $rutaRelativaQr);
            $inscripcion['ruta_qr'] = $rutaRelativaQr;
        }

        return $inscripcion;
    }

    protected function generarPdfCertificado($certificado) {
        $fechaEvento = date('d/m/Y', strtotime($certificado['fecha_evento']));
        $nombre = $this->textoPdf($certificado['nombre_completo']);
        $evento = $this->textoPdf($certificado['evento_titulo']);
        $documento = $this->textoPdf($certificado['documento_identidad']);
        $fecha = $this->textoPdf($fechaEvento);
        $ubicacion = $this->textoPdf($certificado['ubicacion'] ?? 'Neiva');
        $certId = (int) $certificado['id'];

        // Dummy PDF string for now, to fix the syntax error
        // Recomiendo encarecidamente instalar TCPDF o FPDF vía Composer para esto.
        $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        return $pdfContent;
    }

    protected function enviarCorreoCertificado($certificado, $pdf) {
        $mailService = new MailService();
        return $mailService->enviarCorreoCertificado($certificado, $pdf);
    }
}
