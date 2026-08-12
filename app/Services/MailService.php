<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Throwable;

class MailService {
    private $simularEnvio = false;

    private function configureMailer() {
        $smtpUser = $_ENV['SMTP_USER'] ?? (defined('SMTP_USER') ? SMTP_USER : '');
        $smtpPass = $_ENV['SMTP_PASS'] ?? (defined('SMTP_PASS') ? SMTP_PASS : '');

        // Placeholders de .env.example => tratar como "no configurado" y simular
        // el envío (se guarda una copia en scratch/emails) en lugar de fallar.
        $placeholders = ['your-email@gmail.com', 'your-app-password', ''];
        if (in_array(trim($smtpUser), $placeholders, true)
            || in_array(trim($smtpPass), $placeholders, true)) {
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

    /**
     * RNF01: Envío masivo de correos a los inscritos de un evento.
     * $destinatarios: array de ['correo' => ..., 'nombre' => ...].
     * Devuelve la cantidad de correos enviados con éxito.
     */
    public function enviarAvisoEvento(array $destinatarios, $asunto, $mensaje) {
        $enviados = 0;
        $mail = $this->configureMailer();

        foreach ($destinatarios as $d) {
            $correo = trim((string) ($d['correo'] ?? ''));
            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $nombre = (string) ($d['nombre'] ?? '');

            if ($this->simularEnvio) {
                $this->guardarCorreoSimulado($correo, $asunto, $mensaje);
                $enviados++;
                continue;
            }

            try {
                $mail->clearAddresses();
                $mail->addAddress($correo, $nombre);
                $mail->Subject = $asunto;
                $mail->Body = $mensaje;
                if ($mail->send()) {
                    $enviados++;
                }
            } catch (Throwable $e) {
                error_log('Error en MailService (AvisoEvento): ' . $e->getMessage());
            }
        }

        return $enviados;
    }

    public function enviarCorreoBienvenida($datos, $passwordPlano) {
        try {
            $mail = $this->configureMailer();
            $asunto = 'Bienvenido a NeivActiva';
            
            $mensaje = "Hola {$datos['nombre_completo']},\n\n";
            $mensaje .= "Se creo tu cuenta en NeivActiva para gestionar tus inscripciones, QR y certificados.\n\n";
            $appUrl = rtrim($_ENV['APP_URL'] ?? (defined('APP_URL') ? APP_URL : 'http://localhost/NeivActiva'), '/');
            $mensaje .= "Acceso: {$appUrl}/login\n";
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

    /**
     * Correo de confirmación tras el auto-registro del usuario.
     * No incluye contraseña (el usuario la definió al registrarse).
     * Devuelve true si se envió (o se simuló) correctamente.
     */
    public function enviarBienvenidaRegistro(array $datos) {
        try {
            $correo = trim((string) ($datos['correo'] ?? ''));
            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            $nombre = trim((string) ($datos['nombre'] ?? 'usuario'));
            $appName = $_ENV['APP_NAME'] ?? (defined('APP_NAME') ? APP_NAME : 'NeivActiva');
            $appUrl  = rtrim($_ENV['APP_URL'] ?? (defined('APP_URL') ? APP_URL : 'http://localhost/NeivActiva'), '/');
            $loginUrl = $appUrl . '/login';
            $asunto = "¡Ya estás registrado en {$appName}! 🎉";

            $texto  = "Hola {$nombre},\n\n";
            $texto .= "Tu registro en {$appName} se completó correctamente. Ya estás registrado.\n\n";
            $texto .= "Con tu cuenta puedes inscribirte en eventos, obtener tu código QR y descargar certificados.\n\n";
            $texto .= "Inicia sesión aquí: {$loginUrl}\n";
            $texto .= "Correo de acceso: {$correo}\n\n";
            $texto .= "¡Nos vemos en los eventos de Neiva!\n{$appName}\n";

            $nombreHtml = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
            $correoHtml = htmlspecialchars($correo, ENT_QUOTES, 'UTF-8');
            $html = $this->plantillaBienvenidaHtml($appName, $nombreHtml, $correoHtml, $loginUrl);

            // configureMailer() debe llamarse primero: fija $this->simularEnvio.
            $mail = $this->configureMailer();

            if ($this->simularEnvio) {
                return $this->guardarCorreoSimulado($correo, $asunto, $texto);
            }

            $mail->addAddress($correo, $nombre);
            $mail->Subject = $asunto;
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = $texto;
            return $mail->send();
        } catch (Throwable $e) {
            error_log('Error en MailService (BienvenidaRegistro): ' . $e->getMessage());
            return false;
        }
    }

    private function plantillaBienvenidaHtml($appName, $nombre, $correo, $loginUrl) {
        return '<!DOCTYPE html><html lang="es"><body style="margin:0;padding:0;background:#FDFCF9;font-family:Segoe UI,Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FDFCF9;padding:32px 16px;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border:1px solid #EAE3D5;border-radius:20px;overflow:hidden;">'
            . '<tr><td style="background:linear-gradient(135deg,#FF6B35,#C93F10);padding:32px 32px 28px;text-align:center;">'
            . '<div style="font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">☀ ' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<div style="margin-top:10px;font-size:26px;font-weight:800;color:#ffffff;">¡Ya estás registrado! 🎉</div>'
            . '</td></tr>'
            . '<tr><td style="padding:32px;">'
            . '<p style="margin:0 0 16px;font-size:16px;color:#2C2520;">Hola <strong>' . $nombre . '</strong>,</p>'
            . '<p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#5E534C;">Tu cuenta se creó correctamente. Ahora puedes inscribirte en eventos, obtener tu <strong>código QR</strong> y descargar <strong>certificados</strong> digitales.</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;background:#FFF3ED;border:1px solid rgba(255,107,53,0.2);border-radius:12px;"><tr><td style="padding:12px 16px;font-size:14px;color:#5E534C;">Correo de acceso:<br><strong style="color:#2C2520;">' . $correo . '</strong></td></tr></table>'
            . '<div style="text-align:center;margin:8px 0 8px;"><a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:linear-gradient(135deg,#FF6B35,#E8441A);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:999px;">Iniciar sesión →</a></div>'
            . '</td></tr>'
            . '<tr><td style="padding:20px 32px;border-top:1px solid #F6F3EB;text-align:center;font-size:12px;color:#8F8177;">¡Nos vemos en los eventos de Neiva!<br>' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /**
     * Correo con el enlace para restablecer la contraseña.
     */
    public function enviarEnlaceRecuperacion($correo, $nombre, $resetUrl) {
        try {
            $correo = trim((string) $correo);
            if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            $nombre  = trim((string) $nombre);
            $nombre  = $nombre !== '' ? $nombre : 'usuario';
            $appName = $_ENV['APP_NAME'] ?? (defined('APP_NAME') ? APP_NAME : 'NeivActiva');
            $asunto  = "Restablece tu contraseña · {$appName}";

            $texto  = "Hola {$nombre},\n\n";
            $texto .= "Recibimos una solicitud para restablecer tu contraseña en {$appName}.\n\n";
            $texto .= "Abre este enlace para crear una nueva contraseña (válido por 1 hora):\n{$resetUrl}\n\n";
            $texto .= "Si no solicitaste esto, ignora este correo; tu contraseña no cambiará.\n\n{$appName}\n";

            $html = $this->plantillaRecuperacionHtml(
                $appName,
                htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
                $resetUrl
            );

            $mail = $this->configureMailer();

            if ($this->simularEnvio) {
                return $this->guardarCorreoSimulado($correo, $asunto, $texto);
            }

            $mail->addAddress($correo, $nombre);
            $mail->Subject = $asunto;
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = $texto;
            return $mail->send();
        } catch (Throwable $e) {
            error_log('Error en MailService (Recuperacion): ' . $e->getMessage());
            return false;
        }
    }

    private function plantillaRecuperacionHtml($appName, $nombre, $resetUrl) {
        $appNameHtml = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $urlHtml = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        return '<!DOCTYPE html><html lang="es"><body style="margin:0;padding:0;background:#FDFCF9;font-family:Segoe UI,Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FDFCF9;padding:32px 16px;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border:1px solid #EAE3D5;border-radius:20px;overflow:hidden;">'
            . '<tr><td style="background:linear-gradient(135deg,#FF6B35,#C93F10);padding:32px;text-align:center;">'
            . '<div style="font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">☀ ' . $appNameHtml . '</div>'
            . '<div style="margin-top:10px;font-size:24px;font-weight:800;color:#ffffff;">Restablece tu contraseña 🔑</div>'
            . '</td></tr>'
            . '<tr><td style="padding:32px;">'
            . '<p style="margin:0 0 16px;font-size:16px;color:#2C2520;">Hola <strong>' . $nombre . '</strong>,</p>'
            . '<p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#5E534C;">Recibimos una solicitud para restablecer tu contraseña. Pulsa el botón para crear una nueva. El enlace es válido por <strong>1 hora</strong>.</p>'
            . '<div style="text-align:center;margin:24px 0;"><a href="' . $urlHtml . '" style="display:inline-block;background:linear-gradient(135deg,#FF6B35,#E8441A);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:999px;">Crear nueva contraseña</a></div>'
            . '<p style="margin:0 0 8px;font-size:12px;color:#8F8177;">Si el botón no funciona, copia y pega este enlace:</p>'
            . '<p style="margin:0 0 16px;font-size:12px;word-break:break-all;color:#5E534C;"><a href="' . $urlHtml . '" style="color:#C93F10;">' . $urlHtml . '</a></p>'
            . '<p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#8F8177;">Si no solicitaste esto, ignora este correo; tu contraseña no cambiará.</p>'
            . '</td></tr>'
            . '<tr><td style="padding:20px 32px;border-top:1px solid #F6F3EB;text-align:center;font-size:12px;color:#8F8177;">' . $appNameHtml . '</td></tr>'
            . '</table></td></tr></table></body></html>';
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
