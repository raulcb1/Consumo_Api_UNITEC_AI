<?php
require_once __DIR__ . '/../core/modelo.php';

class Linea extends Modelo
{
    protected $tabla = 'lineas';

    public function obtenerPorCodigo(string $linea, int $equipoId = 1)
    {
        return $this->consultarUna(
            "SELECT * FROM lineas WHERE linea = :linea AND equipo_id = :equipo_id AND deleted_at IS NULL",
            ['linea' => (string)$linea, 'equipo_id' => $equipoId]
        );
    }

    public function listarActivas(int $equipoId = 1): array
    {
        return $this->consultar(
            "SELECT * FROM lineas WHERE equipo_id = :equipo_id AND deleted_at IS NULL ORDER BY linea ASC",
            ['equipo_id' => $equipoId]
        );
    }
}
