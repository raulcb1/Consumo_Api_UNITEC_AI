<?php
require_once __DIR__ . '/../core/modelo.php';

class Programa extends Modelo
{
    protected $tabla = 'programas';

    public function listarPorLinea(int $lineaId): array
    {
        return $this->consultar(
            "SELECT * FROM programas 
             WHERE linea_id = :linea_id AND deleted_at IS NULL 
             ORDER BY nombre ASC",
            ['linea_id' => $lineaId]
        );
    }

    public function obtenerPorIdApi(int $programaIdApi, int $lineaId)
    {
        return $this->consultarUna(
            "SELECT * FROM programas 
             WHERE programa_id_api = :pid AND linea_id = :lid AND deleted_at IS NULL",
            ['pid' => $programaIdApi, 'lid' => $lineaId]
        );
    }

    public function listarPorLineaYTipo(int $lineaId, string $tipo): array
    {
        return $this->consultar(
            "SELECT * FROM programas 
             WHERE linea_id = :linea_id AND tipo = :tipo AND deleted_at IS NULL 
             ORDER BY nombre ASC",
            ['linea_id' => $lineaId, 'tipo' => $tipo]
        );
    }
}