<?php
require_once __DIR__ . '/../core/modelo.php';

class DistribucionLote extends Modelo
{
    protected $tabla = 'distribuciones_lote';
    protected $tieneSoftDelete = false;

    public function listarPorConteo(int $conteoId): array
    {
        return $this->consultar(
            "SELECT * FROM distribuciones_lote WHERE conteo_lote_id = :id",
            ['id' => $conteoId]
        );
    }
}
