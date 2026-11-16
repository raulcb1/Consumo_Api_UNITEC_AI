<?php
require_once __DIR__ . '/../core/modelo.php';

class ConteoLote extends Modelo
{
    protected $tabla = 'conteos_lote';

    public function listarPorLote(int $loteId): array
    {
        return $this->consultar(
            "SELECT * FROM conteos_lote 
             WHERE lote_id = :lote_id 
             ORDER BY consultado_en DESC",
            ['lote_id' => $loteId]
        );
    }

    public function obtenerUltimoPorLote(int $loteId)
    {
        return $this->consultarUna(
            "SELECT * FROM conteos_lote 
             WHERE lote_id = :lote_id 
             ORDER BY consultado_en DESC 
             LIMIT 1",
            ['lote_id' => $loteId]
        );
    }
}