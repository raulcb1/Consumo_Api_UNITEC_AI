<?php
require_once __DIR__ . '/../core/modelo.php';

class ConteoDesecho extends Modelo
{
    protected $tabla = 'conteos_desecho';

    public function listarPorConteo(int $conteoLoteId): array
    {
        return $this->consultar(
            "SELECT * FROM conteos_desecho 
             WHERE conteo_lote_id = :id",
            ['id' => $conteoLoteId]
        );
    }
}