<?php

namespace App\Models;

class UsuarioModel extends BaseModel {

    public function __construct() {
        parent::__construct('usuarios');
    }

    public function listar($busqueda = '') {
        $busqueda = trim((string) $busqueda);
        $orderBy = $this->columnExists('creado_en') ? 'creado_en DESC, id DESC' : 'id DESC';

        if ($busqueda === '') {
            return $this->all($orderBy);
        }

        $like = '%' . $busqueda . '%';
        $columnasBusqueda = array_filter(
            ['nombre', 'correo', 'documento_identidad', 'telefono', 'rol'],
            fn($columna) => $this->columnExists($columna)
        );
        if (empty($columnasBusqueda)) {
            return $this->all($orderBy);
        }

        $where = implode(' OR ', array_map(fn($columna) => "$columna LIKE ?", $columnasBusqueda));

        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE $where
             ORDER BY {$this->orderBySeguro($orderBy)}",
            array_fill(0, count($columnasBusqueda), $like)
        )->fetchAll();
    }

    public function login($identificador, $password) {
        $identificador = trim((string) $identificador);
        if ($this->columnExists('documento_identidad')) {
            $sql = "SELECT * FROM {$this->table}
                    WHERE correo = ?
                       OR (documento_identidad IS NOT NULL AND documento_identidad = ?)
                    LIMIT 1";
            $user = $this->db->query($sql, [$identificador, $identificador])->fetch();
        } else {
            $user = $this->db->query(
                "SELECT * FROM {$this->table} WHERE correo = ? LIMIT 1",
                [$identificador]
            )->fetch();
        }

        if (!$user) {
            return false;
        }

        $hashAlmacenado = (string) ($user['password'] ?? '');
        $hashInfo = password_get_info($hashAlmacenado);
        $passwordValido = false;

        if (($hashInfo['algo'] ?? 0) !== 0) {
            $passwordValido = password_verify($password, $hashAlmacenado);
            if ($passwordValido && password_needs_rehash($hashAlmacenado, PASSWORD_DEFAULT)) {
                $this->actualizarPasswordHash((int) $user['id'], $password);
            }
        } else {
            $passwordValido = hash_equals($hashAlmacenado, (string) $password);
            if ($passwordValido) {
                $this->actualizarPasswordHash((int) $user['id'], $password);
            }
        }

        return $passwordValido ? $user : false;
    }

    public function registrar($datos, $rol = 'cliente') {
        return $this->crear([
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'documento_identidad' => $datos['documento_identidad'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'password' => password_hash($datos['password'], PASSWORD_DEFAULT),
            'rol' => $rol,
        ]);
    }

    public function crear($datos) {
        return $this->create([
            'nombre' => trim((string) $datos['nombre']),
            'correo' => strtolower(trim((string) $datos['correo'])),
            'documento_identidad' => $this->valorNullable($datos['documento_identidad'] ?? null),
            'telefono' => $this->valorNullable($datos['telefono'] ?? null),
            'password' => password_hash((string) $datos['password'], PASSWORD_DEFAULT),
            'rol' => $this->rolValido($datos['rol'] ?? 'cliente'),
        ]);
    }

    public function actualizarUsuario($id, $datos) {
        $payload = [
            'nombre' => trim((string) $datos['nombre']),
            'correo' => strtolower(trim((string) $datos['correo'])),
            'documento_identidad' => $this->valorNullable($datos['documento_identidad'] ?? null),
            'telefono' => $this->valorNullable($datos['telefono'] ?? null),
            'rol' => $this->rolValido($datos['rol'] ?? 'cliente'),
        ];

        if (!empty($datos['password'])) {
            $payload['password'] = password_hash((string) $datos['password'], PASSWORD_DEFAULT);
        }

        return $this->update((int) $id, $payload);
    }

    public function completarDatosBasicos($usuarioId, $datos) {
        return $this->db->query(
            "UPDATE {$this->table}
             SET nombre = COALESCE(NULLIF(nombre, ''), ?),
                 documento_identidad = COALESCE(documento_identidad, NULLIF(?, '')),
                 telefono = COALESCE(telefono, NULLIF(?, ''))
             WHERE id = ?",
            [
                $datos['nombre'] ?? '',
                $datos['documento_identidad'] ?? '',
                $datos['telefono'] ?? '',
                (int) $usuarioId
            ]
        );
    }

    public function existeCorreo($correo, $exceptoId = null) {
        $params = [strtolower(trim((string) $correo))];
        $sql = "SELECT id FROM {$this->table} WHERE correo = ?";

        if ($exceptoId !== null) {
            $sql .= " AND id <> ?";
            $params[] = (int) $exceptoId;
        }

        $sql .= " LIMIT 1";
        return (bool) $this->db->query($sql, $params)->fetch();
    }

    public function existeDocumento($documento, $exceptoId = null) {
        if (trim((string) $documento) === '') {
            return false;
        }

        if (!$this->columnExists('documento_identidad')) {
            return false;
        }

        $params = [trim((string) $documento)];
        $sql = "SELECT id FROM {$this->table} WHERE documento_identidad = ?";

        if ($exceptoId !== null) {
            $sql .= " AND id <> ?";
            $params[] = (int) $exceptoId;
        }

        $sql .= " LIMIT 1";
        return (bool) $this->db->query($sql, $params)->fetch();
    }

    public function buscarPorCorreo($correo) {
        return $this->db->query(
            "SELECT * FROM {$this->table} WHERE correo = ? LIMIT 1",
            [strtolower(trim((string) $correo))]
        )->fetch();
    }

    public function buscarPorDocumento($documento) {
        if (trim((string) $documento) === '') {
            return false;
        }

        if (!$this->columnExists('documento_identidad')) {
            return false;
        }

        return $this->db->query(
            "SELECT * FROM {$this->table}
             WHERE documento_identidad IS NOT NULL
             AND documento_identidad = ?
             LIMIT 1",
            [trim((string) $documento)]
        )->fetch();
    }

    public function buscarPorCorreoODocumento($correo, $documento) {
        if (!$this->columnExists('documento_identidad')) {
            return $this->buscarPorCorreo($correo);
        }

        return $this->db->query(
            "SELECT * FROM {$this->table}
             WHERE correo = ?
                OR (documento_identidad IS NOT NULL AND documento_identidad = ?)
             LIMIT 1",
            [strtolower(trim((string) $correo)), trim((string) $documento)]
        )->fetch();
    }

    public function validarDatos($datos, $id = null) {
        $errores = [];
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $correo = strtolower(trim((string) ($datos['correo'] ?? '')));
        $documento = trim((string) ($datos['documento_identidad'] ?? ''));
        $telefono = trim((string) ($datos['telefono'] ?? ''));
        $password = (string) ($datos['password'] ?? '');
        $rol = $datos['rol'] ?? '';

        if ($nombre === '' || strlen($nombre) < 3) {
            $errores[] = 'El nombre debe tener al menos 3 caracteres.';
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no es valido.';
        } elseif ($this->existeCorreo($correo, $id)) {
            $errores[] = 'El correo ya esta registrado.';
        }

        if ($documento !== '' && $this->existeDocumento($documento, $id)) {
            $errores[] = 'El documento ya esta registrado.';
        }

        if ($telefono !== '' && strlen($telefono) < 7) {
            $errores[] = 'El telefono debe tener al menos 7 caracteres.';
        }

        if (!$id && strlen($password) < 8) {
            $errores[] = 'La contrasena debe tener al menos 8 caracteres.';
        }

        if ($id && $password !== '' && strlen($password) < 8) {
            $errores[] = 'La nueva contrasena debe tener al menos 8 caracteres.';
        }

        if (!in_array($rol, $this->rolesPermitidos(), true)) {
            $errores[] = 'El rol seleccionado no es valido.';
        }

        return $errores;
    }

    public function rolesPermitidos() {
        return ['admin', 'organizador', 'cliente', 'participante'];
    }

    private function rolValido($rol) {
        return in_array($rol, $this->rolesPermitidos(), true) ? $rol : 'cliente';
    }

    private function valorNullable($valor) {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }

    private function actualizarPasswordHash($id, $password) {
        return $this->update((int) $id, [
            'password' => password_hash((string) $password, PASSWORD_DEFAULT),
        ]);
    }
}
