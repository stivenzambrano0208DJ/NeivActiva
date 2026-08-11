<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\MailService;
use App\Services\CalendarService;
use App\Services\UserEventsService;
use App\Controllers\ApiController;
use Exception;
use Throwable;

class EventController extends Controller {
    public function inscribir_evento_ajax() {
        header('Content-Type: application/json; charset=utf-8');

        $rol = $_SESSION['rol'] ?? 'invitado';
        if ($rol === 'participante') {
            $rol = 'cliente';
        }
        if (!in_array($rol, ['cliente', 'organizador', 'admin'], true)) {
            echo json_encode([
                'ok' => false,
                'code' => 'login_required',
                'msg' => 'Inicia sesion para inscribirte.'
            ]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => 'Metodo no permitido.']);
            exit();
        }

        if (!$this->validarCsrf()) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'code' => 'csrf', 'msg' => 'La sesion expiro. Recarga la pagina e intenta nuevamente.']);
            exit();
        }

        $eventoId = (int) ($_POST['evento_id'] ?? 0);
        $eventoDisponible = $this->eventos->obtenerDisponiblePorId($eventoId);
        if (!$eventoDisponible) {
            echo json_encode(['ok' => false, 'code' => 'sin_cupos', 'msg' => 'Evento lleno o cerrado.']);
            exit();
        }

        $correoSesion = strtolower($_SESSION['usuario_correo'] ?? '');
        if ($this->inscripciones->existeInscripcionActivaPorEmail($correoSesion, $eventoId)) {
            echo json_encode(['ok' => false, 'code' => 'ya_inscrito', 'msg' => 'Ya estas inscrito en este evento.']);
            exit();
        }

        $participanteExistente = $this->participantes->buscarPorCorreo($correoSesion);
        $datosParticipante = [
            'nombre_completo' => $this->limpiarTexto($_POST['nombre_completo'] ?? ($participanteExistente['nombre_completo'] ?? ($_SESSION['usuario_nombre'] ?? ''))),
            'correo_electronico' => $correoSesion,
            'documento_identidad' => $this->limpiarTexto($_POST['documento_identidad'] ?? ($participanteExistente['documento_identidad'] ?? '')),
            'telefono' => $this->limpiarTexto($_POST['telefono'] ?? ($participanteExistente['telefono'] ?? '')),
        ];

        $errorValidacion = $this->validarParticipante($datosParticipante);
        if ($errorValidacion) {
            echo json_encode([
                'ok' => false,
                'code' => 'perfil_incompleto',
                'msg' => 'Completa tus datos para generar la inscripcion.',
                'participante' => [
                    'nombre_completo' => $datosParticipante['nombre_completo'],
                    'correo_electronico' => $datosParticipante['correo_electronico'],
                    'documento_identidad' => $datosParticipante['documento_identidad'],
                    'telefono' => $datosParticipante['telefono']
                ]
            ]);
            exit();
        }

        $pdo = Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();

            $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
            $datosParticipante['usuario_id'] = $usuarioId ?: null;
            $participante = $this->participantes->crearOActualizar($datosParticipante);
            if (!$participante['ok']) {
                $pdo->rollBack();
                echo json_encode(['ok' => false, 'code' => 'duplicado', 'msg' => 'Documento o correo ya registrado con otros datos.']);
                exit();
            }

            if ($this->inscripciones->existeInscripcionActiva($participante['id'], $eventoId)
                || $this->inscripciones->existeInscripcionActivaPorEmail($correoSesion, $eventoId)) {
                $pdo->rollBack();
                echo json_encode(['ok' => false, 'code' => 'ya_inscrito', 'msg' => 'Ya estas inscrito en este evento.']);
                exit();
            }

            if (!$this->eventos->actualizarCupo($eventoId)) {
                $pdo->rollBack();
                echo json_encode(['ok' => false, 'code' => 'sin_cupos', 'msg' => 'Evento lleno o cerrado.']);
                exit();
            }

            $tokenQr = bin2hex(random_bytes(32));
            $datos = [
                'evento_id' => $eventoId,
                'participante_id' => $participante['id'],
                'usuario_id' => $usuarioId ?: null,
                'nombre_completo' => $datosParticipante['nombre_completo'],
                'correo_electronico' => $datosParticipante['correo_electronico'],
                'documento_identidad' => $datosParticipante['documento_identidad'],
                'telefono' => $datosParticipante['telefono'],
                'categoria_participacion' => 'General',
                'estado_inscripcion' => 'Confirmada',
                'token_qr' => $tokenQr
            ];

            $inscripcionId = $this->inscripciones->registrar($datos);
            $inscripcionQr = array_merge($datos, [
                'id' => $inscripcionId,
                'evento_titulo' => $eventoDisponible['titulo'],
            ]);

            $nombreQr = 'qr_inscripcion_' . $inscripcionId . '_' . substr(hash('sha256', $tokenQr), 0, 16) . '.png';
            $rutaRelativaQr = '/public/uploads/qrs/' . $nombreQr;
            $this->qrService->generarPng($this->crearPayloadQr($inscripcionQr), ROOT_PATH . $rutaRelativaQr);
            $this->inscripciones->guardarQr($inscripcionId, $rutaRelativaQr);

            $pdo->commit();

            $inscritosActuales = min((int) $eventoDisponible['cupo_maximo'], (int) $eventoDisponible['inscritos_actuales'] + 1);
            $cuposDisponibles = max(0, (int) $eventoDisponible['cupo_maximo'] - $inscritosActuales);

            echo json_encode([
                'ok' => true,
                'msg' => 'Inscripcion exitosa',
                'inscripcion_id' => $inscripcionId,
                'evento_id' => $eventoId,
                'inscritos_actuales' => $inscritosActuales,
                'cupos_disponibles' => $cuposDisponibles,
                'qr_url' => '?view=ver_qr&id=' . $inscripcionId . '&token=' . urlencode($tokenQr),
                'descargar_url' => '?view=descargar_qr&id=' . $inscripcionId . '&token=' . urlencode($tokenQr),
                'token' => $tokenQr
            ]);
            exit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->logError('Error en inscripcion AJAX', ['evento_id' => $eventoId, 'error' => $e->getMessage()]);
            echo json_encode(['ok' => false, 'code' => 'error', 'msg' => 'No se pudo completar la inscripcion.']);
            exit();
        }
    }

    public function enviar_qr_ajax() {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireRole(['cliente', 'organizador', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->validarCsrf()) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'msg' => 'La sesion expiro. Recarga la pagina e intenta nuevamente.']);
            exit();
        }

        $inscripcion = $this->obtenerInscripcionAutorizada((int) ($_POST['id'] ?? 0), $_POST['token'] ?? '');
        if (!$inscripcion) {
            echo json_encode(['ok' => false, 'msg' => 'QR no disponible.']);
            exit();
        }

        try {
            $enviado = $this->enviarCorreoQr($inscripcion);
        } catch (Throwable $e) {
            $enviado = false;
            $this->logError('Error enviando QR AJAX', ['inscripcion_id' => $inscripcion['id'], 'error' => $e->getMessage()]);
        }

        echo json_encode([
            'ok' => (bool) $enviado,
            'msg' => $enviado ? 'QR enviado al correo.' : 'No se pudo enviar el correo. La inscripcion sigue confirmada.'
        ]);
        exit();
    }

    public function inscripcion() {
        $lista_eventos = $this->eventos->obtenerEventosDisponibles();
        $ultimaInscripcion = null;
        $csrfToken = $this->csrfToken();

        if (isset($_GET['exito'], $_SESSION['ultima_inscripcion_id'])) {
            $ultimaInscripcion = $this->inscripciones->obtenerPorId((int) $_SESSION['ultima_inscripcion_id']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf()) {
                header("Location: ?view=inscripcion&error=csrf");
                exit();
            }

            $eventoId = (int) ($_POST['evento_id'] ?? 0);
            $eventoDisponible = null;
            foreach ($lista_eventos as $evento) {
                if ((int) $evento['id'] === $eventoId) {
                    $eventoDisponible = $evento;
                    break;
                }
            }

            if (!$eventoDisponible) {
                header("Location: ?view=inscripcion&error=evento_cerrado");
                exit();
            }

            $participanteDatos = $this->datosParticipanteDesdePost();
            $participanteDatos['nombre'] = $participanteDatos['nombre_completo'];
            $errorValidacion = $this->validarParticipante($participanteDatos);
            if ($errorValidacion) {
                $_SESSION['inscripcion_errores'] = [];
                header("Location: ?view=inscripcion&error=$errorValidacion");
                exit();
            }

            $pdo = Database::getInstance()->getConnection();
            try {
                $pdo->beginTransaction();

                $participante = $this->participantes->crearOActualizar($participanteDatos);
                if (!$participante['ok']) {
                    $pdo->rollBack();
                    header("Location: ?view=inscripcion&error=duplicado");
                    exit();
                }

                $duplicadoPorCorreo = $participanteDatos['correo_electronico'] !== ''
                    && $this->inscripciones->existeInscripcionActivaPorEmail($participanteDatos['correo_electronico'], $eventoId);

                if ($this->inscripciones->existeInscripcionActiva($participante['id'], $eventoId) || $duplicadoPorCorreo) {
                    $pdo->rollBack();
                    header("Location: ?view=inscripcion&error=ya_inscrito");
                    exit();
                }

                if (!$this->eventos->actualizarCupo($eventoId)) {
                    $pdo->rollBack();
                    header("Location: ?view=inscripcion&error=evento_cerrado");
                    exit();
                }

                $tokenQr = bin2hex(random_bytes(32));
                $datos = [
                    'evento_id' => $eventoId,
                    'participante_id' => $participante['id'],
                    'usuario_id' => null,
                    'nombre_completo' => $participanteDatos['nombre_completo'],
                    'correo_electronico' => $participanteDatos['correo_electronico'],
                    'documento_identidad' => $participanteDatos['documento_identidad'],
                    'telefono' => $participanteDatos['telefono'],
                    'categoria_participacion' => $this->validarCategoriaParticipacion($_POST['categoria'] ?? 'Adulto'),
                    'estado_inscripcion' => 'Confirmada',
                    'token_qr' => $tokenQr
                ];

                $inscripcionId = $this->inscripciones->registrar($datos);
                $inscripcionQr = array_merge($datos, [
                    'id' => $inscripcionId,
                    'evento_titulo' => $eventoDisponible['titulo'],
                ]);

                $nombreQr = 'qr_inscripcion_' . $inscripcionId . '_' . substr(hash('sha256', $tokenQr), 0, 16) . '.png';
                $rutaRelativaQr = '/public/uploads/qrs/' . $nombreQr;
                $this->qrService->generarPng($this->crearPayloadQr($inscripcionQr), ROOT_PATH . $rutaRelativaQr);
                $this->inscripciones->guardarQr($inscripcionId, $rutaRelativaQr);

                $pdo->commit();

                $inscripcionCompleta = $this->inscripciones->obtenerPorId($inscripcionId);
                $correoOk = true;
                if ($inscripcionCompleta) {
                    $correoOk = $this->enviarCorreoQr($inscripcionCompleta) && $correoOk;
                }

                $_SESSION['ultima_inscripcion_id'] = $inscripcionId;
                $_SESSION['ultimo_qr_token'] = $tokenQr;
                $correoMsg = $correoOk ? '&correo=1' : '&correo_error=1';
                header("Location: ?view=inscripcion&exito=1&download=1$correoMsg");
                exit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $this->logError('Error al registrar inscripcion', ['error' => $e->getMessage()]);
                header("Location: ?view=inscripcion&error=registro");
                exit();
            }
        }
        require ROOT_PATH . '/resources/views/inscripcion.php';
    }

    public function descargar_qr() {
        $inscripcion = $this->obtenerInscripcionAutorizada((int) ($_GET['id'] ?? 0), $_GET['token'] ?? '');
        if (!$inscripcion || empty($inscripcion['ruta_qr'])) {
            header("Location: ?view=inscripcion&error=qr_no_disponible");
            exit();
        }

        $rutaFisica = ROOT_PATH . $inscripcion['ruta_qr'];
        if (!file_exists($rutaFisica)) {
            header("Location: ?view=inscripcion&error=qr_no_disponible");
            exit();
        }

        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="qr_neivactiva_' . (int) $inscripcion['id'] . '.png"');
        header('Content-Length: ' . filesize($rutaFisica));
        readfile($rutaFisica);
        exit();
    }

    public function ver_qr() {
        $inscripcion = $this->obtenerInscripcionAutorizada((int) ($_GET['id'] ?? 0), $_GET['token'] ?? '');
        if (!$inscripcion || empty($inscripcion['ruta_qr'])) {
            http_response_code(404);
            exit('QR no disponible');
        }

        $rutaFisica = ROOT_PATH . $inscripcion['ruta_qr'];
        if (!file_exists($rutaFisica)) {
            http_response_code(404);
            exit('QR no disponible');
        }

        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($rutaFisica));
        header('Cache-Control: private, max-age=300');
        readfile($rutaFisica);
        exit();
    }

    public function enviar_qr() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->validarCsrf()) {
            header("Location: ?view=inscripcion&error=csrf");
            exit();
        }

        $inscripcion = $this->obtenerInscripcionAutorizada((int) ($_POST['id'] ?? 0), $_POST['token'] ?? '');
        if (!$inscripcion) {
            header("Location: ?view=inscripcion&error=qr_no_disponible");
            exit();
        }

        try {
            $enviado = $this->enviarCorreoQr($inscripcion);
            if (!$enviado) {
                $this->logError('No se pudo enviar QR por correo', ['inscripcion_id' => $inscripcion['id']]);
            }
        } catch (Throwable $e) {
            $enviado = false;
            $this->logError('Error enviando QR por correo', [
                'inscripcion_id' => $inscripcion['id'],
                'error' => $e->getMessage()
            ]);
        }

        $_SESSION['ultima_inscripcion_id'] = (int) $inscripcion['id'];
        $_SESSION['ultimo_qr_token'] = $inscripcion['token_qr'] ?? $inscripcion['datos_qr'];

        $volver = $_POST['volver'] ?? 'inscripcion';
        $vistaRetorno = in_array($volver, ['mis_qr', 'inscripcion'], true) ? $volver : 'inscripcion';
        $extra = $vistaRetorno === 'inscripcion' ? 'exito=1&' : '';
        header("Location: ?view=$vistaRetorno&" . $extra . ($enviado ? "correo=1" : "correo_error=1"));
        exit();
    }

    public function mis_certificados() {
        $this->requireRole(['cliente', 'organizador', 'admin']);
        
        $email = $_SESSION['usuario_correo'] ?? ''; // Necesito asegurar que el correo esté en la sesión
        $mis_inscripciones = $this->inscripciones->obtenerPorEmail($email);
        $csrfToken = $this->csrfToken();
        
        require ROOT_PATH . '/resources/views/mis_certificados.php';
    }

    public function mis_qr() {
        $this->requireRole(['cliente', 'organizador', 'admin']);

        $email = $_SESSION['usuario_correo'] ?? '';
        $mis_inscripciones = $this->inscripciones->obtenerPorEmail($email);
        $mis_qr = [];
        $csrfToken = $this->csrfToken();

        foreach ($mis_inscripciones as $inscripcion) {
            try {
                $mis_qr[] = $this->asegurarQrInscripcion($inscripcion);
            } catch (Throwable $e) {
                $this->logError('No se pudo preparar QR de inscripcion', [
                    'inscripcion_id' => $inscripcion['id'] ?? null,
                    'error' => $e->getMessage()
                ]);
            }
        }

        require ROOT_PATH . '/resources/views/mis_qr.php';
    }

    public function detalle_evento() {
        $this->requireRole(['cliente', 'organizador', 'admin']);

        $evento = $this->eventos->find((int) ($_GET['id'] ?? 0));
        if (!$evento) {
            header("Location: ?view=calendario&error=evento_no_encontrado");
            exit();
        }

        require ROOT_PATH . '/resources/views/detalle_evento.php';
    }

    public function descargar_certificado() {
        $this->requireRole(['cliente', 'organizador', 'admin']);

        $certificado = $this->obtenerCertificadoDisponible((int) ($_GET['id'] ?? 0));
        $pdf = $this->generarPdfCertificado($certificado);
        $nombreArchivo = 'certificado_neivactiva_' . (int) $certificado['id'] . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit();
    }

    public function enviar_certificado() {
        $this->requireRole(['cliente', 'organizador', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->validarCsrf()) {
            header("Location: ?view=mis_certificados");
            exit();
        }

        $certificado = $this->obtenerCertificadoDisponible((int) ($_POST['id'] ?? 0));
        $pdf = $this->generarPdfCertificado($certificado);
        $enviado = $this->enviarCorreoCertificado($certificado, $pdf);

        header("Location: ?view=mis_certificados&" . ($enviado ? "correo=1" : "correo_error=1"));
        exit();
    }

    protected function procesarImagenEvento() {
        if (empty($_FILES['imagen']['name'])) {
            return null;
        }

        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            header("Location: ?view=gestionar_eventos&error=subida");
            exit();
        }

        $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $tipoArchivo = mime_content_type($_FILES['imagen']['tmp_name']);

        if (!isset($tiposPermitidos[$tipoArchivo])) {
            header("Location: ?view=gestionar_eventos&error=imagen");
            exit();
        }

        $directorioDestino = ROOT_PATH . '/public/uploads/eventos';
        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0775, true);
        }

        $nombreArchivo = uniqid('evento_', true) . '.' . $tiposPermitidos[$tipoArchivo];
        $rutaDestino = $directorioDestino . '/' . $nombreArchivo;

        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            header("Location: ?view=gestionar_eventos&error=subida");
            exit();
        }

        return '/uploads/eventos/' . $nombreArchivo;
    }

    protected function datosEventoDesdePost($rutaImagen = null) {
        $horaEvento = trim($_POST['hora_evento'] ?? '');
        if ($horaEvento !== '' && preg_match('/^\d{2}:\d{2}$/', $horaEvento)) {
            $horaEvento .= ':00';
        }

        return [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'ubicacion' => trim($_POST['ubicacion'] ?? ''),
            'fecha_evento' => $_POST['fecha_evento'] ?? '',
            'hora_evento' => $horaEvento,
            'cupo_maximo' => max(1, (int) ($_POST['cupo_maximo'] ?? 1)),
            'categoria' => $_POST['categoria'] ?? 'Otro',
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'ruta_imagen' => $rutaImagen
        ];
    }

    protected function validarDatosEvento($datos) {
        foreach (['titulo', 'ubicacion', 'fecha_evento', 'hora_evento', 'categoria', 'descripcion'] as $campo) {
            if (($datos[$campo] ?? '') === '') {
                return false;
            }
        }

        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $datos['hora_evento'])
            && in_array($datos['categoria'], ['Deportivo', 'Cultural', 'Educativo', 'Otro'], true);
    }

    public function gestionar_eventos() {
        $this->requireRole(['organizador', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? 'crear_evento';
            $eventoId = (int) ($_POST['evento_id'] ?? 0);
            $rol = $_SESSION['rol'] ?? 'invitado';
            $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);

            if (($_POST['accion'] ?? '') === 'terminar_evento') {
                if ($eventoId > 0 && $this->eventos->usuarioPuedeEditar($eventoId, $rol, $usuarioId)) {
                    $this->eventos->marcarTerminado($eventoId);
                    header("Location: ?view=gestionar_eventos&terminado=1");
                    exit();
                }
                header("Location: ?view=gestionar_eventos&error=permiso");
                exit();
            }

            if ($accion === 'editar_evento' && ($eventoId <= 0 || !$this->eventos->usuarioPuedeEditar($eventoId, $rol, $usuarioId))) {
                header("Location: ?view=gestionar_eventos&error=permiso");
                exit();
            }

            $rutaImagen = $this->procesarImagenEvento();
            $datosEvento = $this->datosEventoDesdePost($rutaImagen);

            if (!$this->validarDatosEvento($datosEvento)) {
                header("Location: ?view=gestionar_eventos&error=validacion");
                exit();
            }

            if ($accion === 'editar_evento') {
                $this->eventos->actualizar($eventoId, $datosEvento);
                header("Location: ?view=gestionar_eventos&actualizado=1");
                exit();
            }

            $datosEvento['organizador_id'] = $usuarioId ?: null;
            $this->eventos->crear($datosEvento);

            header("Location: ?view=gestionar_eventos&exito=1");
            exit();
        }

        $lista_eventos = $this->eventos->obtenerEventos();
        require ROOT_PATH . '/resources/views/gestionar_eventos.php';
    }

    public function gestionar_inscripciones() {
        $this->requireRole(['organizador', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'actualizar_asistencia') {
            $inscripcionId = (int) ($_POST['inscripcion_id'] ?? 0);
            $estado = $_POST['estado_asistencia'] ?? 'Pendiente';

            if ($inscripcionId > 0) {
                $this->inscripciones->actualizarAsistencia($inscripcionId, $estado, (int) ($_SESSION['usuario_id'] ?? 0));
            }

            header("Location: ?view=gestionar_inscripciones&asistencia=1");
            exit();
        }

        $lista_eventos = $this->eventos->obtenerEventos();
        $recientes = $this->inscripciones->obtenerRecientes(1000);
        require ROOT_PATH . '/resources/views/gestionar_inscripciones.php';
    }

    public function asistencia() {
        $this->requireRole(['organizador', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'actualizar_asistencia') {
            $inscripcionId = (int) ($_POST['inscripcion_id'] ?? 0);
            $estado = $_POST['estado_asistencia'] ?? 'Pendiente';

            if ($inscripcionId > 0) {
                $this->inscripciones->actualizarAsistencia($inscripcionId, $estado, (int) ($_SESSION['usuario_id'] ?? 0));
            }

            header("Location: ?view=asistencia&asistencia=1");
            exit();
        }

        $recientes = $this->inscripciones->obtenerRecientes();
        require ROOT_PATH . '/resources/views/asistencia.php';
    }

    public function buscar_por_qr() {
        // AJAX endpoint: returns JSON
        header('Content-Type: application/json; charset=utf-8');

        $rol = $_SESSION['rol'] ?? 'invitado';
        if (!in_array($rol, ['organizador', 'admin'], true)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para validar asistencia.']);
            exit();
        }

        $codigo = trim($_POST['codigo'] ?? '');
        if ($codigo === '') {
            echo json_encode(['ok' => false, 'msg' => 'Código vacío']);
            exit();
        }

        $inscripcion = $this->inscripciones->buscarPorQr($codigo);
        if (!$inscripcion) {
            echo json_encode(['ok' => false, 'msg' => 'QR no reconocido']);
            exit();
        }

        if (($inscripcion['estado_inscripcion'] ?? 'Confirmada') !== 'Confirmada') {
            echo json_encode(['ok' => false, 'msg' => 'La inscripcion no esta activa']);
            exit();
        }

        if (($inscripcion['estado_asistencia'] ?? 'Pendiente') === 'Asistio') {
            echo json_encode([
                'ok' => false,
                'code' => 'duplicado',
                'msg' => 'Este participante ya tenia asistencia registrada.',
                'registro' => $this->formatearRegistroAsistencia($inscripcion)
            ]);
            exit();
        }

        $registrado = $this->inscripciones->registrarAsistenciaQr((int) $inscripcion['id'], (int) ($_SESSION['usuario_id'] ?? 0));
        if (!$registrado) {
            echo json_encode(['ok' => false, 'msg' => 'No se pudo registrar la asistencia o ya habia sido marcada.']);
            exit();
        }

        $inscripcion['estado_asistencia'] = 'Asistio';
        $inscripcion['asistencia_en'] = date('Y-m-d H:i:s');
        $inscripcion['asistencia_usuario_id'] = $_SESSION['usuario_id'] ?? null;

        echo json_encode([
            'ok'      => true,
            'inscripcion_id' => (int) $inscripcion['id'],
            'evento_id' => (int) $inscripcion['evento_id'],
            'nombre'  => $inscripcion['nombre_completo'],
            'evento'  => $inscripcion['evento_titulo'],
            'doc'     => $inscripcion['documento_identidad'],
            'estado'  => 'Asistio',
            'estado_inscripcion' => $inscripcion['estado_inscripcion'] ?? 'Confirmada',
            'msg' => 'Asistencia registrada correctamente.',
            'registro' => $this->formatearRegistroAsistencia($inscripcion)
        ]);
        exit();
    }

    protected function formatearRegistroAsistencia($registro) {
        $fechaAsistencia = $registro['asistencia_en'] ?? null;

        return [
            'id' => (int) ($registro['id'] ?? 0),
            'nombre' => $registro['nombre_completo'] ?? '',
            'iniciales' => $this->inicialesNombre($registro['nombre_completo'] ?? ''),
            'documento' => $registro['documento_identidad'] ?? '',
            'evento' => $registro['evento_titulo'] ?? 'Evento General',
            'estado' => $registro['estado_asistencia'] ?? 'Pendiente',
            'fecha' => $fechaAsistencia ? date('d/m/Y', strtotime($fechaAsistencia)) : 'Sin registrar',
            'hora' => $fechaAsistencia ? date('g:i A', strtotime($fechaAsistencia)) : '--:--',
        ];
    }

    protected function inicialesNombre($nombre) {
        $partes = preg_split('/\s+/', trim((string) $nombre));
        $iniciales = '';

        foreach ($partes as $parte) {
            if ($parte !== '') {
                $iniciales .= strtoupper(substr($parte, 0, 1));
            }

            if (strlen($iniciales) >= 2) {
                break;
            }
        }

        return $iniciales ?: 'NA';
    }

    public function mis_eventos_inscritos() {
        $userId = $_SESSION['usuario_id'] ?? null;
        if (!$userId) {
            header("Location: ?view=login");
            exit();
        }

        $userEventsService = new UserEventsService();
        
        // Obtener filtros
        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'search' => $_GET['search'] ?? '',
            'limit' => $_GET['limit'] ?? 50,
            'offset' => $_GET['offset'] ?? 0
        ];
        
        // Obtener datos
        $events = $userEventsService->getUserRegisteredEvents($userId, $filters);
        $metrics = $userEventsService->getUserMetrics($userId);
        $timeline = $userEventsService->getUserActivityTimeline($userId, 5);
        $attendanceRate = $userEventsService->getUserAttendanceRate($userId);
        $popularCategories = $userEventsService->getPopularEventsForUser($userId, 3);
        
        // Procesar eventos para agregar información adicional
        foreach ($events as &$event) {
            $event['status_info'] = $userEventsService->getEventStatus($event);
            $event['days_remaining'] = $userEventsService->getDaysRemaining($event['fecha_evento']);
            $event['occupancy_rate'] = $userEventsService->getOccupancyRate($event['inscritos_actuales'], $event['cupo_maximo']);
            $event['has_certificate'] = $userEventsService->hasCertificateAvailable($event);
        }
        
        require ROOT_PATH . '/resources/views/mis_eventos_inscritos.php';
    }
}
