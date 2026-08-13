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
            'tipo_documento' => Validator::tipoDocumento($_POST['tipo_documento'] ?? 'CC'),
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
        // FPDF trabaja en ISO-8859-1: convertimos cada cadena desde UTF-8.
        // (No usar Helper::textForPdf aqui: ese escapa parentesis/backslash para
        //  un stream PDF crudo, y FPDF ya hace su propio escape internamente.)
        $conv = static function ($texto) {
            $texto = (string) $texto;
            if (function_exists('iconv')) {
                $convertido = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto);
                if ($convertido !== false) {
                    return $convertido;
                }
            }
            return $texto;
        };

        $fechaEvento = !empty($certificado['fecha_evento'])
            ? date('d/m/Y', strtotime($certificado['fecha_evento']))
            : '';
        $nombre = $conv($certificado['nombre_completo'] ?? '');
        $evento = $conv($certificado['evento_titulo'] ?? '');
        $documento = $conv($certificado['documento_identidad'] ?? '');
        $ubicacion = $conv($certificado['ubicacion'] ?? 'Neiva');
        $fecha = $conv($fechaEvento);
        $certId = (int) ($certificado['id'] ?? 0);

        $pdf = new \Fpdf\Fpdf('L', 'mm', 'A4'); // Horizontal A4: 297 x 210 mm
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);

        // Doble marco decorativo
        $pdf->SetDrawColor(230, 126, 34); // Naranja NeivActiva
        $pdf->SetLineWidth(1.5);
        $pdf->Rect(10, 10, 277, 190);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(14, 14, 269, 182);

        // Marca
        $pdf->SetY(34);
        $pdf->SetFont('Arial', 'B', 30);
        $pdf->SetTextColor(230, 126, 34);
        $pdf->Cell(0, 16, $conv('NEIVACTIVA'), 0, 1, 'C');

        // Titulo
        $pdf->SetFont('Arial', 'B', 22);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(0, 14, $conv('CERTIFICADO DE PARTICIPACIÓN'), 0, 1, 'C');

        $pdf->Ln(6);
        $pdf->SetFont('Arial', '', 14);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 10, $conv('Se certifica que'), 0, 1, 'C');

        // Nombre del participante
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 26);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Cell(0, 14, $nombre, 0, 1, 'C');

        if ($documento !== '') {
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetTextColor(110, 110, 110);
            $pdf->Cell(0, 8, $conv('Documento: ') . $documento, 0, 1, 'C');
        }

        // Evento
        $pdf->Ln(4);
        $pdf->SetFont('Arial', '', 14);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 8, $conv('Participó en el evento'), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(230, 126, 34);
        $pdf->MultiCell(0, 10, $evento, 0, 'C');

        // Fecha y lugar
        $detalle = trim($fecha . (($fecha !== '' && $ubicacion !== '') ? '  -  ' : '') . $ubicacion);
        if ($detalle !== '') {
            $pdf->Ln(4);
            $pdf->SetFont('Arial', '', 13);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->Cell(0, 8, $detalle, 0, 1, 'C');
        }

        // Pie con folio verificable
        $pdf->SetY(182);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 6, $conv('Certificado N.º ') . $certId . $conv('  ·  Emitido por NeivActiva'), 0, 0, 'C');

        return $pdf->Output('S');
    }

    protected function enviarCorreoCertificado($certificado, $pdf) {
        $mailService = new MailService();
        return $mailService->enviarCorreoCertificado($certificado, $pdf);
    }
}
