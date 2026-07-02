<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Throwable;

class MailService {
    private $simularEnvio = false;

    private function configureMailer() {
        $smtpUser = $_ENV['SMTP_USER'] ?? (defined('SMTP_USER') ? SMTP_USER : '');

        if (trim($smtpUser) === '') {
            $this->simularEnvio = true;
            return null;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST']   ?? (defined('SMTP_HOST') ? SMTP_HOST : 'localhost');
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $_ENV['SMTP_PASS']   ?? (defined('SMTP_PASS') ? SMTP_PASS : '');
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? (defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS);
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? (defined('SMTP_PORT') ? SMTP_PORT : 587));
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($smtpUser, $_ENV['APP_NAME'] ?? 'NeivActiva');

        return $mail;
    }

    private function guardarCorreoSimulado($destinatario, $asunto, $cuerpo) {
        $rutaDir = ROOT_PATH . '/scratch/emails';
        if (!is_dir($rutaDir)) {
            @mkdir($rutaDir, 0777, true);
        }
        $nombreArchivo = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $destinatario) . '_' . uniqid() . '.txt';
        $contenido = "Para: $destinatario\nAsunto: $asunto\nFecha: " . date('Y-m-d H:i:s') . "\n\n$cuerpo";
        file_put_contents($rutaDir . '/' . $nombreArchivo, $contenido);
        return true;
    }

    public function enviarCorreoBienvenida($datos, $passwordPlano) {
        try {
            $mail = $this->configureMailer();
            $asunto = 'Bienvenido a NeivActiva';
            
            $mensaje = "Hola {$datos['nombre_completo']},\n\n";
            $mensaje .= "Se creo tu cuenta en NeivActiva para gestionar tus inscripciones, QR y certificados.\n\n";
            $appUrl = rtrim($_ENV['APP_URL'] ?? (defined('APP_URL') ? APP_URL : 'http://localhost/NeivActiva'), '/');
            $mensaje .= "Acceso: {$appUrl}/public/login\n";
            $mensaje .= "Correo: {$datos['correo_electronico']}\n";
            $mensaje .= "Contrasena temporal: {$passwordPlano}\n\n";
            $mensaje .= "Por seguridad, cambia tu contrasena despues de ingresar.\n\n";
            $mensaje .= "NeivActiva\n";

            if ($this->simularEnvio) {
                return $this->guardarCorreoSimulado($datos['correo_electronico'], $asunto, $mensaje);
            }

            $mail->addAddress($datos['correo_electronico'], $datos['nombre_completo']);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje;
            return $mail->send();
        } catch (Throwable $e) {
            error_log('Error en MailService (Bienvenida): ' . $e->getMessage());
            return false;
        }
    }

    public function enviarCorreoQr($inscripcion) {
        try {
            if (trim((string) ($inscripcion['correo_electronico'] ?? '')) === '') {
                return false;
            }

            if (empty($inscripcion['ruta_qr'])) {
                return false;
            }

            $rutaFisica = ROOT_PATH . $inscripcion['ruta_qr'];
            if (!file_exists($rutaFisica)) {
                return false;
            }

            $mail = $this->configureMailer();
            $asunto = 'Confirmacion de inscripcion - NeivActiva';
            
            $fechaEvento = date('d/m/Y', strtotime($inscripcion['fecha_evento']));
            $mensaje = "Hola {$inscripcion['nombre_completo']},\n\n";
            $mensaje .= "Tu inscripcion al evento {$inscripcion['evento_titulo']} del {$fechaEvento} fue confirmada.\n";
            $mensaje .= "Adjuntamos tu codigo QR. Presentalo en la entrada del evento para validar tu inscripcion.\n\n";
            $mensaje .= "NeivActiva\n";

            if ($this->simularEnvio) {
                return $this->guardarCorreoSimulado($inscripcion['correo_electronico'], $asunto, $mensaje . "\n\n[Adjunto simulado: QR de inscripcion]");
            }

            $mail->addAddress($inscripcion['correo_electronico'], $inscripcion['nombre_completo']);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje;
            $mail->addAttachment($rutaFisica, 'qr_neivactiva_' . (int) $inscripcion['id'] . '.png');
            
            return $mail->send();
        } catch (Throwable $e) {
            error_log('Error en MailService (QR): ' . $e->getMessage());
            return false;
        }
    }

    public function enviarCorreoCertificado($certificado, $pdf) {
        try {
            $mail = $this->configureMailer();
            $asunto = 'Tu certificado NeivActiva';
            
            $mensaje = "Hola {$certificado['nombre_completo']},\n\nAdjuntamos tu certificado del evento {$certificado['evento_titulo']}.\n\nNeivActiva\n";
            
            if ($this->simularEnvio) {
                return $this->guardarCorreoSimulado($certificado['correo_electronico'], $asunto, $mensaje . "\n\n[Adjunto simulado: Certificado PDF en Base64...]");
            }

            $mail->addAddress($certificado['correo_electronico'], $certificado['nombre_completo']);
            $mail->Subject = $asunto;
            $mail->Body = $mensaje;
            $mail->addStringAttachment($pdf, 'certificado_neivactiva_' . (int) $certificado['id'] . '.pdf', 'base64', 'application/pdf');
            
            return $mail->send();
        } catch (Throwable $e) {
            error_log('Error en MailService (Certificado): ' . $e->getMessage());
            return false;
        }
    }
}
