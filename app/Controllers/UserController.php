<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\MailService;
use App\Services\UserEventsService;
use Exception;
use RuntimeException;
use Throwable;

class UserController extends Controller {
    public function verificar_participante_cuenta() {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireRole(['organizador', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->validarCsrf()) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'msg' => 'La sesion expiro. Recarga la pagina e intenta nuevamente.']);
            exit();
        }

        $correo = strtolower($this->limpiarTexto($_POST['correo_electronico'] ?? ''));
        $documento = $this->limpiarTexto($_POST['documento_identidad'] ?? '');

        if ($correo === '' && $documento === '') {
            echo json_encode(['ok' => false, 'msg' => 'Ingresa correo o documento.']);
            exit();
        }

        $usuarioPorCorreo = $correo !== '' ? $this->usuarios->buscarPorCorreo($correo) : false;
        $usuarioPorDocumento = $documento !== '' ? $this->usuarios->buscarPorDocumento($documento) : false;
        $conflictoCuentas = $usuarioPorCorreo
            && $usuarioPorDocumento
            && (int) $usuarioPorCorreo['id'] !== (int) $usuarioPorDocumento['id'];
        $usuario = $usuarioPorCorreo ?: $usuarioPorDocumento;
        if (!$usuario) {
            echo json_encode(['ok' => true, 'exists' => false]);
            exit();
        }

        $conflicto = $conflictoCuentas || !$this->cuentaCoincideConParticipante($usuario, [
            'correo_electronico' => $correo,
            'documento_identidad' => $documento
        ]);

        echo json_encode([
            'ok' => true,
            'exists' => true,
            'conflict' => $conflicto,
            'msg' => $conflicto
                ? 'El documento pertenece a otra cuenta registrada.'
                : 'El participante ya tiene una cuenta registrada.',
            'usuario' => [
                'id' => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
                'correo' => $usuario['correo'],
                'rol' => $usuario['rol']
            ]
        ]);
        exit();
    }

    protected function datosParticipanteAdminDesdePost() {
        return [
            // El formulario admin envia nombre_completo/documento_identidad/
            // correo_electronico; aceptamos tambien los nombres cortos por si acaso.
            'nombre' => $this->limpiarTexto($_POST['nombre_completo'] ?? $_POST['nombre'] ?? ''),
            'documento' => $this->limpiarTexto($_POST['documento_identidad'] ?? $_POST['documento'] ?? ''),
            'tipo_documento' => \App\Core\Validator::tipoDocumento($_POST['tipo_documento'] ?? 'CC'),
            'telefono' => $this->limpiarTexto($_POST['telefono'] ?? ''),
            'correo' => strtolower($this->limpiarTexto($_POST['correo_electronico'] ?? $_POST['correo'] ?? '')),
            'fecha_nacimiento' => $this->limpiarTexto($_POST['fecha_nacimiento'] ?? ''),
            'genero' => $this->limpiarTexto($_POST['genero'] ?? ''),
            'ciudad' => $this->limpiarTexto($_POST['ciudad'] ?? ''),
            'institucion' => $this->limpiarTexto($_POST['institucion'] ?? ''),
            'observaciones' => $this->limpiarTexto($_POST['observaciones'] ?? ''),
        ];
    }

    protected function prepararQrInscripcionAdmin($inscripcionId, $tokenQr, $eventoTitulo = '') {
        $inscripcion = $this->inscripciones->obtenerPorId($inscripcionId);
        if (!$inscripcion) {
            return false;
        }

        if ($eventoTitulo !== '') {
            $inscripcion['evento_titulo'] = $eventoTitulo;
        }

        $nombreQr = 'qr_inscripcion_' . (int) $inscripcionId . '_' . substr(hash('sha256', $tokenQr), 0, 16) . '.png';
        $rutaRelativaQr = '/public/uploads/qrs/' . $nombreQr;
        $this->qrService->generarPng($this->crearPayloadQr($inscripcion), ROOT_PATH . $rutaRelativaQr);
        $this->inscripciones->guardarQr($inscripcionId, $rutaRelativaQr);

        return true;
    }

    public function participantes() {
        $this->requireRole(['organizador', 'admin']);

        $csrfToken = $this->csrfToken();
        $busqueda = $this->limpiarTexto($_GET['q'] ?? '');
        $participanteEditar = null;
        $historialParticipante = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf()) {
                header("Location: ?view=participantes&error=csrf");
                exit();
            }

            $accion = $_POST['accion'] ?? '';
            $participanteId = (int) ($_POST['participante_id'] ?? 0);

            try {
                if ($accion === 'eliminar') {
                    if ($participanteId <= 0) {
                        header("Location: ?view=participantes&error=no_encontrado");
                        exit();
                    }

                    $this->participantes->delete($participanteId);
                    header("Location: ?view=participantes&msg=eliminado");
                    exit();
                }

                $datos = $this->datosParticipanteAdminDesdePost();
                $errores = $this->participantes->validar($datos, $accion === 'actualizar' ? $participanteId : null);

                if (!empty($errores)) {
                    $_SESSION['participantes_form_errors'] = $errores;
                    $_SESSION['participantes_form_old'] = $datos + ['id' => $participanteId];
                    header("Location: ?view=participantes" . ($accion === 'actualizar' ? '&editar=' . $participanteId : '') . "&error=validacion");
                    exit();
                }

                if ($accion === 'actualizar') {
                    $this->participantes->actualizarParticipante($participanteId, $datos);
                    header("Location: ?view=participantes&msg=actualizado");
                    exit();
                }

                if ($accion === 'crear') {
                    $this->participantes->crear($datos);
                    header("Location: ?view=participantes&msg=creado");
                    exit();
                }
            } catch (Throwable $e) {
                $this->logError('Error gestionando participante', ['error' => $e->getMessage()]);
                header("Location: ?view=participantes&error=bd");
                exit();
            }
        }

        if (isset($_GET['editar'])) {
            $participanteEditar = $this->participantes->find((int) $_GET['editar']);
        }

        if (isset($_GET['historial'])) {
            $historialId = (int) $_GET['historial'];
            $participanteHistorial = $this->participantes->find($historialId);
            $historialParticipante = $participanteHistorial ? $this->participantes->historial($historialId) : [];
        } else {
            $participanteHistorial = null;
        }

        $formErrors = $_SESSION['participantes_form_errors'] ?? [];
        $oldInput = $_SESSION['participantes_form_old'] ?? [];
        unset($_SESSION['participantes_form_errors'], $_SESSION['participantes_form_old']);

        $lista_participantes = $this->participantes->listar($busqueda);
        require ROOT_PATH . '/resources/views/participantes.php';
    }

    protected function leerArchivoCargaMasiva($ruta, $extension) {
        $extension = strtolower(trim((string) $extension));

        if (!is_uploaded_file($ruta) && !is_file($ruta)) {
            throw new RuntimeException('No se encontro el archivo temporal.');
        }

        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            return $this->leerArchivoCargaMasivaConPhpSpreadsheet($ruta, $extension);
        }

        if ($extension === 'csv') {
            return $this->leerCsvCargaMasiva($ruta);
        }

        if ($extension === 'xlsx') {
            return $this->leerXlsxCargaMasiva($ruta);
        }

        throw new RuntimeException('Formato no soportado. Usa CSV o XLSX.');
    }

    protected function leerArchivoCargaMasivaConPhpSpreadsheet($ruta, $extension) {
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('Formato no soportado. Usa CSV o XLSX.');
        }

        if ($extension === 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setDelimiter($this->detectarDelimitadorCsv($ruta));
            $reader->setEnclosure('"');
            if (method_exists($reader, 'setInputEncoding')) {
                $reader->setInputEncoding($this->detectarEncodingArchivo($ruta));
            }
        } else {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        }

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $spreadsheet = $reader->load($ruta);
        $sheet = $spreadsheet->getActiveSheet();
        $filas = $sheet->toArray('', false, false, false);
        $spreadsheet->disconnectWorksheets();

        return $this->normalizarFilasCargaMasiva($filas);
    }

    protected function leerCsvCargaMasiva($ruta) {
        $handle = fopen($ruta, 'rb');
        if (!$handle) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }

        $filas = [];
        $delimitador = $this->detectarDelimitadorCsv($ruta);

        while (($fila = fgetcsv($handle, 0, $delimitador)) !== false) {
            $filas[] = array_map(fn($valor) => $this->normalizarTextoCargaMasiva($valor), $fila);
        }
        fclose($handle);

        return $this->normalizarFilasCargaMasiva($filas);
    }

    protected function leerXlsxCargaMasiva($ruta) {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Para leer XLSX habilita ZipArchive o instala PhpSpreadsheet.');
        }

        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        $shared = $this->leerSharedStringsXlsx($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('El XLSX no tiene una hoja principal legible.');
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            throw new RuntimeException('El XLSX no contiene filas legibles.');
        }

        $filas = [];
        foreach ($xml->sheetData->row as $row) {
            $fila = [];
            $posicionSiguiente = 0;

            foreach ($row->c as $cell) {
                $referencia = (string) $cell['r'];
                $indice = $referencia !== '' ? $this->indiceColumnaExcel($referencia) : $posicionSiguiente;
                $fila[$indice] = $this->valorCeldaXlsx($cell, $shared);
                $posicionSiguiente = $indice + 1;
            }

            if (!empty($fila)) {
                ksort($fila);
                $max = max(array_keys($fila));
                $filas[] = array_replace(array_fill(0, $max + 1, ''), $fila);
            }
        }

        return $this->normalizarFilasCargaMasiva($filas);
    }

    protected function leerSharedStringsXlsx($zip) {
        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml === false) {
            return $shared;
        }

        $xml = simplexml_load_string($sharedXml);
        if (!$xml) {
            return $shared;
        }

        foreach ($xml->si as $si) {
            $partes = $si->xpath('.//*[local-name()="t"]');
            $texto = '';
            if ($partes) {
                foreach ($partes as $parte) {
                    $texto .= (string) $parte;
                }
            } else {
                $texto = (string) ($si->t ?? '');
            }
            $shared[] = $this->normalizarTextoCargaMasiva($texto);
        }

        return $shared;
    }

    protected function valorCeldaXlsx($cell, $shared) {
        $tipo = (string) $cell['t'];

        if ($tipo === 's') {
            return $shared[(int) ($cell->v ?? 0)] ?? '';
        }

        if ($tipo === 'inlineStr') {
            $partes = $cell->xpath('.//*[local-name()="t"]');
            $texto = '';
            if ($partes) {
                foreach ($partes as $parte) {
                    $texto .= (string) $parte;
                }
            }
            return $this->normalizarTextoCargaMasiva($texto);
        }

        return $this->normalizarTextoCargaMasiva((string) ($cell->v ?? ''));
    }

    protected function indiceColumnaExcel($referencia) {
        preg_match('/^([A-Z]+)/i', $referencia, $matches);
        $letras = strtoupper($matches[1] ?? 'A');
        $indice = 0;

        for ($i = 0; $i < strlen($letras); $i++) {
            $indice = ($indice * 26) + (ord($letras[$i]) - 64);
        }

        return max(0, $indice - 1);
    }

    protected function detectarDelimitadorCsv($ruta) {
        $handle = fopen($ruta, 'rb');
        if (!$handle) {
            return ',';
        }

        $muestra = '';
        for ($i = 0; $i < 5 && !feof($handle); $i++) {
            $muestra .= (string) fgets($handle);
        }
        fclose($handle);

        $candidatos = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
        foreach ($candidatos as $delimitador => $_) {
            $candidatos[$delimitador] = substr_count($muestra, $delimitador);
        }

        arsort($candidatos);
        $delimitador = array_key_first($candidatos);

        return ($candidatos[$delimitador] ?? 0) > 0 ? $delimitador : ',';
    }

    protected function detectarEncodingArchivo($ruta) {
        $muestra = (string) file_get_contents($ruta, false, null, 0, 4096);
        $encoding = mb_detect_encoding($muestra, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);

        return $encoding ?: 'UTF-8';
    }

    protected function normalizarTextoCargaMasiva($valor) {
        if ($valor === null) {
            return '';
        }

        $texto = (string) $valor;
        $texto = str_replace(["\xEF\xBB\xBF", "\xc2\xa0"], ['', ' '], $texto);

        if (!mb_check_encoding($texto, 'UTF-8')) {
            $encoding = mb_detect_encoding($texto, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true) ?: 'Windows-1252';
            $texto = mb_convert_encoding($texto, 'UTF-8', $encoding);
        }

        $texto = preg_replace('/\s+/u', ' ', $texto);

        return trim($texto);
    }

    protected function normalizarEncabezadoCargaMasiva($valor) {
        $texto = mb_strtolower($this->normalizarTextoCargaMasiva($valor), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n',
        ]);
        $texto = preg_replace('/[^a-z0-9]+/u', ' ', $texto);

        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    protected function mapaColumnasCargaMasiva() {
        return [
            'nombre' => ['nombre', 'nombre completo', 'nombres', 'nombres apellidos', 'participante', 'name'],
            'documento' => ['documento', 'documento identidad', 'cedula', 'identificacion', 'numero documento', 'no documento', 'dni'],
            'telefono' => ['telefono', 'celular', 'movil', 'numero celular', 'phone'],
            'correo' => ['correo', 'correo electronico', 'email', 'e mail', 'mail'],
            'fecha_nacimiento' => ['fecha nacimiento', 'fecha de nacimiento', 'nacimiento'],
            'genero' => ['genero', 'sexo'],
            'ciudad' => ['ciudad', 'municipio'],
            'institucion' => ['institucion', 'entidad', 'empresa', 'organizacion'],
            'observaciones' => ['observaciones', 'observacion', 'nota', 'notas'],
        ];
    }

    /**
     * Convierte las filas crudas del archivo (matriz de celdas) en registros
     * asociativos con los campos canonicos. La primera fila con contenido se
     * toma como encabezado y sus columnas se mapean via mapaColumnasCargaMasiva().
     */
    protected function normalizarFilasCargaMasiva($filas) {
        // Descartar filas totalmente vacias, conservando el orden.
        $filas = array_values(array_filter($filas, function ($fila) {
            if (!is_array($fila)) {
                return false;
            }
            foreach ($fila as $valor) {
                if (trim((string) $valor) !== '') {
                    return true;
                }
            }
            return false;
        }));

        if (empty($filas)) {
            return [];
        }

        // Primera fila = encabezado. Mapear cada columna a un campo canonico.
        $encabezado = array_shift($filas);
        $mapa = $this->mapaColumnasCargaMasiva();
        $columnas = []; // indice de columna -> campo canonico

        foreach ($encabezado as $indice => $titulo) {
            $normalizado = $this->normalizarEncabezadoCargaMasiva($titulo);
            if ($normalizado === '') {
                continue;
            }
            foreach ($mapa as $campo => $alias) {
                if (in_array($normalizado, $alias, true)) {
                    $columnas[$indice] = $campo;
                    break;
                }
            }
        }

        $camposBase = [
            'nombre' => '', 'documento' => '', 'telefono' => '', 'correo' => '',
            'fecha_nacimiento' => '', 'genero' => '', 'ciudad' => '',
            'institucion' => '', 'observaciones' => '',
        ];

        $registros = [];
        $numeroFila = 1; // el encabezado ocupa la fila 1
        foreach ($filas as $fila) {
            $numeroFila++;
            $registro = array_merge(['fila' => $numeroFila], $camposBase);
            foreach ($columnas as $indice => $campo) {
                $registro[$campo] = $this->normalizarTextoCargaMasiva($fila[$indice] ?? '');
            }
            $registros[] = $registro;
        }

        return $registros;
    }

    public function carga_masiva() {
        $this->requireRole(['admin']);

        $csrfToken = $this->csrfToken();
        $preview = $_SESSION['carga_masiva_preview'] ?? [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf()) {
                header("Location: ?view=carga_masiva&error=csrf");
                exit();
            }

            $accion = $_POST['accion'] ?? 'preview';
            try {
                if ($accion === 'preview') {
                    if (empty($_FILES['archivo']['tmp_name'])) {
                        header("Location: ?view=carga_masiva&error=archivo");
                        exit();
                    }

                    $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
                    $preview = $this->leerArchivoCargaMasiva($_FILES['archivo']['tmp_name'], $extension);
                    $_SESSION['carga_masiva_preview'] = $preview;
                    header("Location: ?view=carga_masiva&msg=preview");
                    exit();
                }

                if ($accion === 'importar') {
                    $eventoId = (int) ($_POST['evento_id'] ?? 0);
                    $incluirAdvertencias = ($_POST['incluir_advertencias'] ?? '1') === '1';
                    $importados = 0;
                    $omitidos = 0;

                    foreach ($preview as $registro) {
                        $validacion = $this->participantes->validarCargaMasiva($registro);
                        if (!empty($validacion['errores']) || (!$incluirAdvertencias && !empty($validacion['advertencias']))) {
                            $omitidos++;
                            continue;
                        }

                        $participante = $this->participantes->crearOActualizar($registro);
                        if (!$participante['ok']) {
                            $omitidos++;
                            continue;
                        }

                        $importados++;
                        if ($eventoId > 0 && !$this->inscripciones->existeParticipanteEnEvento($participante['id'], $eventoId)) {
                            $evento = $this->eventos->obtenerDisponiblePorId($eventoId);
                            if ($evento && $this->eventos->actualizarCupo($eventoId)) {
                                $tokenQr = bin2hex(random_bytes(32));
                                $inscripcionId = $this->inscripciones->registrarParticipanteEvento(
                                    $this->participantes->find($participante['id']),
                                    $eventoId,
                                    $tokenQr
                                );
                                $this->prepararQrInscripcionAdmin($inscripcionId, $tokenQr, $evento['titulo']);
                            }
                        }
                    }

                    unset($_SESSION['carga_masiva_preview']);
                    header("Location: ?view=carga_masiva&msg=importado&ok=$importados&omitidos=$omitidos");
                    exit();
                }

                if ($accion === 'descargar_errores') {
                    $this->descargarErroresCargaMasiva($preview);
                }
            } catch (Throwable $e) {
                $this->logError('Error en carga masiva', ['error' => $e->getMessage()]);
                $_SESSION['carga_masiva_error'] = $e->getMessage();
                header("Location: ?view=carga_masiva&error=procesar");
                exit();
            }
        }

        $errorDetalle = $_SESSION['carga_masiva_error'] ?? '';
        unset($_SESSION['carga_masiva_error']);
        $lista_eventos = $this->eventos->obtenerEventosDisponibles();
        foreach ($preview as &$registroPreview) {
            $validacionPreview = $this->participantes->validarCargaMasiva($registroPreview);
            $registroPreview['errores'] = $validacionPreview['errores'];
            $registroPreview['advertencias'] = $validacionPreview['advertencias'];
            $registroPreview['estado'] = $validacionPreview['estado'];
        }
        unset($registroPreview);
        require ROOT_PATH . '/resources/views/carga_masiva.php';
    }

    protected function descargarErroresCargaMasiva($preview) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="incidencias_carga_participantes.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['fila', 'estado', 'nombre', 'documento', 'correo', 'telefono', 'errores', 'advertencias']);

        foreach ($preview as $registro) {
            $validacion = $this->participantes->validarCargaMasiva($registro);
            if ($validacion['estado'] === 'valid') {
                continue;
            }

            fputcsv($out, [
                $registro['fila'] ?? '',
                $validacion['estado'],
                $registro['nombre'] ?? '',
                $registro['documento'] ?? '',
                $registro['correo'] ?? '',
                $registro['telefono'] ?? '',
                implode(' | ', $validacion['errores']),
                implode(' | ', $validacion['advertencias']),
            ]);
        }

        fclose($out);
        exit();
    }

    public function descargar_plantilla_participantes() {
        $this->requireRole(['organizador', 'admin']);

        $headers = [
            'nombre',
            'documento',
            'telefono',
            'correo',
            'fecha_nacimiento',
            'genero',
            'ciudad',
            'institucion',
            'observaciones'
        ];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla_participantes_neivactiva.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        fputcsv($out, [
            'Ana Maria Rojas',
            '1000000001',
            '3001234567',
            'ana@example.com',
            '2000-05-15',
            'Femenino',
            'Neiva',
            'Institucion ejemplo',
            'Observacion opcional'
        ]);
        fclose($out);
        exit();
    }

    protected function datosUsuarioDesdePost() {
        return [
            'nombre' => $this->limpiarTexto($_POST['nombre'] ?? ''),
            'correo' => strtolower($this->limpiarTexto($_POST['correo'] ?? '')),
            'documento_identidad' => $this->limpiarTexto($_POST['documento_identidad'] ?? ''),
            'tipo_documento' => \App\Core\Validator::tipoDocumento($_POST['tipo_documento'] ?? 'CC'),
            'telefono' => $this->limpiarTexto($_POST['telefono'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'rol' => $this->limpiarTexto($_POST['rol'] ?? 'cliente'),
        ];
    }

    public function usuarios() {
        $this->requireRole(['admin']);

        $csrfToken = $this->csrfToken();
        $busqueda = $this->limpiarTexto($_GET['q'] ?? '');
        $usuarioEditar = null;
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validarCsrf()) {
                header("Location: ?view=usuarios&error=csrf");
                exit();
            }

            $accion = $_POST['accion'] ?? '';
            $usuarioId = (int) ($_POST['usuario_id'] ?? 0);

            try {
                if ($accion === 'eliminar') {
                    if ($usuarioId <= 0) {
                        header("Location: ?view=usuarios&error=no_encontrado");
                        exit();
                    }

                    if ($usuarioId === (int) ($_SESSION['usuario_id'] ?? 0)) {
                        header("Location: ?view=usuarios&error=self_delete");
                        exit();
                    }

                    $this->usuarios->delete($usuarioId);
                    header("Location: ?view=usuarios&msg=eliminado");
                    exit();
                }

                $datos = $this->datosUsuarioDesdePost();
                $idValidacion = $accion === 'actualizar' ? $usuarioId : null;
                $errores = $this->usuarios->validarDatos($datos, $idValidacion);

                if (!empty($errores)) {
                    $_SESSION['usuarios_form_errors'] = $errores;
                    $_SESSION['usuarios_form_old'] = $datos + ['id' => $usuarioId];
                    header("Location: ?view=usuarios" . ($accion === 'actualizar' && $usuarioId > 0 ? '&editar=' . $usuarioId : '') . "&error=validacion");
                    exit();
                }

                if ($accion === 'actualizar') {
                    if ($usuarioId <= 0 || !$this->usuarios->find($usuarioId)) {
                        header("Location: ?view=usuarios&error=no_encontrado");
                        exit();
                    }

                    $this->usuarios->actualizarUsuario($usuarioId, $datos);
                    header("Location: ?view=usuarios&msg=actualizado");
                    exit();
                }

                if ($accion === 'crear') {
                    $this->usuarios->crear($datos);
                    header("Location: ?view=usuarios&msg=creado");
                    exit();
                }
            } catch (Throwable $e) {
                $this->logError('Error gestionando usuario', ['error' => $e->getMessage()]);
                header("Location: ?view=usuarios&error=bd");
                exit();
            }
        }

        if (isset($_GET['editar'])) {
            $usuarioEditar = $this->usuarios->find((int) $_GET['editar']);
            if (!$usuarioEditar) {
                header("Location: ?view=usuarios&error=no_encontrado");
                exit();
            }
        }

        $formErrors = $_SESSION['usuarios_form_errors'] ?? [];
        $oldInput = $_SESSION['usuarios_form_old'] ?? [];
        unset($_SESSION['usuarios_form_errors'], $_SESSION['usuarios_form_old']);

        $lista_usuarios = $this->usuarios->listar($busqueda);
        $rolesPermitidos = $this->usuarios->rolesPermitidos();
        require ROOT_PATH . '/resources/views/usuarios.php';
    }
}
