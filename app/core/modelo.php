<?php
// =====================================================================
// modelo.php - Modelo base con CRUD genérico y soft delete automático
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Modelo
{
    protected $db;
    protected $tabla;

    // Caché estática: evita consultar SHOW COLUMNS en cada operación
    private static $cacheSoftDelete = [];

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    // -----------------------------------------------------------------
    // Detecta si la tabla tiene la columna deleted_at (con caché)
    // -----------------------------------------------------------------
    protected function tieneSoftDelete(): bool
    {
        if (isset(self::$cacheSoftDelete[$this->tabla])) {
            return self::$cacheSoftDelete[$this->tabla];
        }
        $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->tabla} LIKE 'deleted_at'");
        $stmt->execute();
        self::$cacheSoftDelete[$this->tabla] = $stmt->rowCount() > 0;
        return self::$cacheSoftDelete[$this->tabla];
    }

    // -----------------------------------------------------------------
    // Listar todos los registros
    // -----------------------------------------------------------------
    public function listar(bool $incluirEliminados = false): array
    {
        $sql = "SELECT * FROM {$this->tabla}";
        if (!$incluirEliminados && $this->tieneSoftDelete()) {
            $sql .= " WHERE deleted_at IS NULL";
        }
        $sql .= " ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // -----------------------------------------------------------------
    // Obtener un registro por ID
    // -----------------------------------------------------------------
    public function obtenerPorId(int $id)
    {
        $sql = "SELECT * FROM {$this->tabla} WHERE id = :id";
        if ($this->tieneSoftDelete()) {
            $sql .= " AND deleted_at IS NULL";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // -----------------------------------------------------------------
    // Crear registro. Devuelve ID insertado o false.
    // -----------------------------------------------------------------
    public function crear(array $datos)
    {
        $campos = array_keys($datos);
        $placeholders = ':' . implode(', :', $campos);
        $sql = "INSERT INTO {$this->tabla} (" . implode(', ', $campos) . ") VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($datos) ? (int) $this->db->lastInsertId() : false;
    }

    // -----------------------------------------------------------------
    // Actualizar registro existente
    // -----------------------------------------------------------------
    public function actualizar(int $id, array $datos): bool
    {
        if (empty($datos)) {
            return false;
        }
        $set = [];
        foreach (array_keys($datos) as $campo) {
            $set[] = "$campo = :$campo";
        }
        $sql = "UPDATE {$this->tabla} SET " . implode(', ', $set) . " WHERE id = :id";
        $datos['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($datos);
    }

    // -----------------------------------------------------------------
    // Eliminar: soft delete si la tabla lo soporta, físico si no
    // -----------------------------------------------------------------
    public function eliminar(int $id): bool
    {
        if ($this->tieneSoftDelete()) {
            $sql = "UPDATE {$this->tabla} SET deleted_at = NOW() WHERE id = :id";
        } else {
            $sql = "DELETE FROM {$this->tabla} WHERE id = :id";
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // -----------------------------------------------------------------
    // Restaurar un registro eliminado lógicamente
    // -----------------------------------------------------------------
    public function restaurar(int $id): bool
    {
        if (!$this->tieneSoftDelete()) {
            return false;
        }
        $sql = "UPDATE {$this->tabla} SET deleted_at = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // -----------------------------------------------------------------
    // Helpers para consultas personalizadas
    // -----------------------------------------------------------------
    protected function consultar(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function consultarUna(string $sql, array $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    protected function ejecutar(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}