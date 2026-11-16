<?php
require_once __DIR__ . '/../core/modelo.php';

class ProcesamientoLote extends Modelo
{
    protected $tabla = 'procesamientos_lotes';

    public function listarPorLote(int $loteId): array
    {
        return $this->consultar(
            "SELECT * FROM procesamientos_lotes 
             WHERE lote_id = :lote_id 
             ORDER BY created_at DESC",
            ['lote_id' => $loteId]
        );
    }

    public function obtenerActualPorLote(int $loteId)
    {
        return $this->consultarUna(
            "SELECT * FROM procesamientos_lotes 
             WHERE lote_id = :lote_id AND es_actual = 1 
             LIMIT 1",
            ['lote_id' => $loteId]
        );
    }

    public function marcarTodosNoActuales(int $loteId): bool
    {
        return (bool) $this->ejecutar(
            "UPDATE procesamientos_lotes SET es_actual = 0 WHERE lote_id = :lote_id",
            ['lote_id' => $loteId]
        );
    }
}