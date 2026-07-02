<?php

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;

class BaseModel {
    protected $db;
    protected $table;

    public function __construct($table) {
        $this->db = Database::getInstance();
        $this->table = $this->validarIdentificador($table);
    }

    public function all($orderBy = 'id DESC') {
        $orderBySeguro = $this->orderBySeguro($orderBy);
        return $this->db->query("SELECT * FROM {$this->table} ORDER BY {$orderBySeguro}")->fetchAll();
    }

    public function find($id) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [(int) $id])->fetch();
    }

    public function create($datos) {
        $datos = $this->filtrarColumnasExistentes($datos);
        if (empty($datos)) {
            throw new InvalidArgumentException('No hay datos validos para insertar.');
        }

        $columnas = array_keys($datos);
        $placeholders = implode(', ', array_fill(0, count($columnas), '?'));
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columnas) . ") VALUES ($placeholders)";
        $this->db->query($sql, array_values($datos));

        return (int) $this->db->getConnection()->lastInsertId();
    }

    public function update($id, $datos) {
        $datos = $this->filtrarColumnasExistentes($datos);
        unset($datos['id']);

        if (empty($datos)) {
            return false;
        }

        $sets = array_map(function ($columna) {
            return "$columna = ?";
        }, array_keys($datos));

        $params = array_values($datos);
        $params[] = (int) $id;

        return $this->db->query(
            "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?",
            $params
        );
    }

    public function delete($id) {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [(int) $id]);
    }

    public function columnExists($column) {
        $column = $this->validarIdentificador($column);
        return (bool) $this->db->query(
            "SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1",
            [$this->table, $column]
        )->fetch();
    }

    protected function orderBySeguro($orderBy) {
        $partes = array_map('trim', explode(',', (string) $orderBy));
        $ordenes = [];

        foreach ($partes as $parte) {
            if ($parte === '') {
                continue;
            }

            if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)(\s+(ASC|DESC))?$/i', $parte, $matches)) {
                continue;
            }

            $columna = $matches[1];
            if (!$this->columnExists($columna)) {
                continue;
            }

            $direccion = strtoupper($matches[3] ?? 'ASC');
            $ordenes[] = $columna . ' ' . ($direccion === 'DESC' ? 'DESC' : 'ASC');
        }

        return $ordenes ? implode(', ', $ordenes) : 'id DESC';
    }

    protected function filtrarColumnasExistentes($datos) {
        $filtrados = [];
        foreach ($datos as $columna => $valor) {
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $columna) && $this->columnExists($columna)) {
                $filtrados[$columna] = $valor;
            }
        }

        return $filtrados;
    }

    private function validarIdentificador($identificador) {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', (string) $identificador)) {
            throw new InvalidArgumentException('Identificador SQL invalido.');
        }

        return $identificador;
    }
}
