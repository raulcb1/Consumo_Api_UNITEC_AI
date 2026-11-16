<?php
require_once __DIR__ . '/../core/modelo.php';

class EquipoApi extends Modelo
{
    protected $tabla = 'equipos_api';

    public function listarTodos(): array
    {
        return $this->consultar(
            "SELECT * FROM equipos_api WHERE deleted_at IS NULL ORDER BY id ASC"
        );
    }

    public function listarActivos(): array
    {
        return $this->consultar(
            "SELECT * FROM equipos_api WHERE activo = 1 AND deleted_at IS NULL ORDER BY nombre ASC"
        );
    }

    public function obtenerPorId(int $id)
    {
        return $this->consultarUna(
            "SELECT * FROM equipos_api WHERE id = :id AND deleted_at IS NULL",
            ['id' => $id]
        );
    }
}
