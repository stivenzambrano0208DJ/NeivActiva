<?php

namespace App\Models;

class ParticipantModel extends BaseModel {

    public function __construct() {
        parent::__construct('participantes');
    }

    public function listar($busqueda = '') {
        $busqueda = trim((string) $busqueda);
        $orderBy = $this->columnExists('creado_en') ? 'creado_en DESC, id DESC' : 'id DESC';

        if ($busqueda === '') {
            return $this->all($orderBy);
        }

        $columnas = array_filter(
            ['nombre', 'documento', 'correo', 'telefono', 'ciudad', 'institucion', 'nombre_completo', 'correo_electronico', 'documento_identidad'],
            fn($columna) => $this->columnExists($columna)
        );

        if (empty($columnas)) {
            return $this->all($orderBy);
        }

        $where = implode(' OR ', array_map(fn($columna) => "$columna LIKE ?", $columnas));

        return $this->db->query(
            "SELECT * FROM {$this->table}
             WHERE $where
             ORDER BY {$this->orderBySeguro($orderBy)}",
            array_fill(0, count($columnas), '%' . $busqueda . '%')
        )->fetchAll();
    }

    public function crear($datos) {
        $datos = $this->normalizarDatos($datos);
        return $this->create($this->payloadCompatible($datos));
    }

    public function actualizarParticipante($id, $datos) {
        $datos = $this->normalizarDatos($datos);
        return $this->update((int) $id, $this->payloadCompatible($datos));
    }

    public function crearOActualizar($datos) {
        $datos = $this->normalizarDatos($datos);
        $existente = $this->buscarPorDocumentoOCorreo($datos['documento'], $datos['correo']);

        if ($existente) {
            $mismoDocumento = $this->documentoDe($existente) === $datos['documento'];
            $mismoCorreo = $datos['correo'] !== ''
                && strtolower($this->correoDe($existente)) === strtolower($datos['correo']);

            if (!$mismoDocumento && !$mismoCorreo) {
                return ['ok' => false, 'error' => 'duplicado_conflicto'];
            }

            $this->actualizarParticipante((int) $existente['id'], $datos);
            return ['ok' => true, 'id' => (int) $existente['id'], 'existente' => true];
        }

        return ['ok' => true, 'id' => $this->crear($datos), 'existente' => false];
    }

    public function buscarPorDocumentoOCorreo($documento, $correo) {
        $documento = trim((string) $documento);
        $correo = strtolower(trim((string) $correo));
        $where = [];
        $params = [];

        foreach (['documento', 'documento_identidad'] as $columna) {
            if ($documento !== '' && $this->columnExists($columna)) {
                $where[] = "$columna = ?";
                $params[] = $documento;
            }
        }

        foreach (['correo', 'correo_electronico'] as $columna) {
            if ($correo !== '' && $this->columnExists($columna)) {
                $where[] = "$columna = ?";
                $params[] = $correo;
            }
        }

        if (empty($where)) {
            return false;
        }

        return $this->db->query(
            "SELECT * FROM {$this->table} WHERE " . implode(' OR ', $where) . " LIMIT 1",
            $params
        )->fetch();
    }

    public function buscarPorCorreo($correo) {
        return $this->buscarPorDocumentoOCorreo('', $correo);
    }

    public function buscarPorDocumento($documento) {
        return $this->buscarPorDocumentoOCorreo($documento, '');
    }

    public function historial($participanteId) {
        return $this->db->query(
            "SELECT i.*, e.titulo AS evento_titulo, e.fecha_evento, e.hora_evento, e.ubicacion
             FROM inscripciones i
             LEFT JOIN eventos e ON e.id = i.evento_id
             WHERE i.participante_id = ?
             ORDER BY COALESCE(i.fecha_inscripcion, i.creado_en) DESC",
            [(int) $participanteId]
        )->fetchAll();
    }

    public function validar($datos, $id = null) {
        $datos = $this->normalizarDatos($datos);
        $errores = [];

        if (strlen($datos['nombre']) < 2) {
            $errores[] = 'El nombre es obligatorio.';
        } elseif (!\App\Core\Validator::esNombreValido($datos['nombre'])) {
            $errores[] = 'El nombre solo puede contener letras y espacios.';
        }

        if (strlen($datos['documento']) < 4) {
            $errores[] = 'El documento debe tener al menos 4 caracteres.';
        } elseif (!\App\Core\Validator::esDocumentoNumerico($datos['documento'])) {
            $errores[] = 'El documento debe contener solo numeros.';
        }

        if ($datos['correo'] !== '' && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no es valido.';
        }

        if ($datos['telefono'] !== '' && strlen($datos['telefono']) < 7) {
            $errores[] = 'El telefono debe tener al menos 7 caracteres.';
        }

        $existente = $this->buscarPorDocumentoOCorreo($datos['documento'], $datos['correo']);
        if ($existente && (int) $existente['id'] !== (int) $id) {
            $errores[] = 'Ya existe un participante con ese documento o correo.';
        }

        return $errores;
    }

    public function validarCargaMasiva($datos) {
        $datos = $this->normalizarDatos($datos);
        $errores = [];
        $advertencias = [];

        if (strlen($datos['nombre']) < 2) {
            $errores[] = 'Nombre obligatorio o demasiado corto.';
        }

        if (strlen($datos['documento']) < 4) {
            $errores[] = 'Documento incompleto.';
        }

        if ($datos['correo'] !== '' && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Correo con formato invalido.';
        }

        if ($datos['telefono'] !== '' && strlen($datos['telefono']) < 7) {
            $advertencias[] = 'Telefono corto; se puede importar y corregir despues.';
        }

        $existenteDocumento = $datos['documento'] !== '' ? $this->buscarPorDocumento($datos['documento']) : false;
        $existenteCorreo = $datos['correo'] !== '' ? $this->buscarPorCorreo($datos['correo']) : false;

        if ($existenteDocumento && $existenteCorreo && (int) $existenteDocumento['id'] !== (int) $existenteCorreo['id']) {
            $errores[] = 'Documento y correo pertenecen a participantes diferentes.';
        } elseif ($existenteDocumento || $existenteCorreo) {
            $advertencias[] = 'Coincide con un participante existente; se actualizara el registro.';
        }

        return [
            'errores' => $errores,
            'advertencias' => $advertencias,
            'estado' => !empty($errores) ? 'error' : (!empty($advertencias) ? 'warning' : 'valid'),
        ];
    }

    public function normalizarDatos($datos) {
        $nombre = trim((string) (
            $datos['nombre']
            ?? $datos['nombre_completo']
            ?? ''
        ));

        return [
            'nombre' => $nombre,
            'documento' => trim((string) ($datos['documento'] ?? $datos['documento_identidad'] ?? '')),
            'tipo_documento' => \App\Core\Validator::tipoDocumento($datos['tipo_documento'] ?? 'CC'),
            'telefono' => trim((string) ($datos['telefono'] ?? '')),
            'correo' => strtolower(trim((string) ($datos['correo'] ?? $datos['correo_electronico'] ?? ''))),
            'fecha_nacimiento' => trim((string) ($datos['fecha_nacimiento'] ?? '')),
            'genero' => trim((string) ($datos['genero'] ?? '')),
            'ciudad' => trim((string) ($datos['ciudad'] ?? '')),
            'institucion' => trim((string) ($datos['institucion'] ?? '')),
            'observaciones' => trim((string) ($datos['observaciones'] ?? '')),
            'usuario_id' => $datos['usuario_id'] ?? null,
        ];
    }

    public function nombreCompletoDe($participante) {
        return (string) (
            $participante['nombre']
            ?? $participante['nombre_completo']
            ?? ''
        );
    }

    public function documentoDe($participante) {
        return (string) ($participante['documento'] ?? $participante['documento_identidad'] ?? '');
    }

    public function correoDe($participante) {
        return (string) ($participante['correo'] ?? $participante['correo_electronico'] ?? '');
    }

    private function payloadCompatible($datos) {
        return [
            'usuario_id' => $datos['usuario_id'] ?: null,
            'nombre' => $datos['nombre'],
            'documento' => $datos['documento'],
            'tipo_documento' => $datos['tipo_documento'] ?? 'CC',
            'telefono' => $datos['telefono'],
            'correo' => $datos['correo'] ?: null,
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?: null,
            'genero' => $datos['genero'],
            'ciudad' => $datos['ciudad'],
            'institucion' => $datos['institucion'],
            'observaciones' => $datos['observaciones'],
            'nombre_completo' => $datos['nombre'],
            'correo_electronico' => $datos['correo'] ?: null,
            'documento_identidad' => $datos['documento'],
        ];
    }
}
