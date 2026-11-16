<?php
require_once __DIR__ . '/../core/modelo.php';

class PromedioLote extends Modelo
{
    protected $tabla = 'promedios_lote';

    public function listarPorConteo(int $conteoLoteId): array
    {
        return $this->consultar(
            "SELECT * FROM promedios_lote 
             WHERE conteo_lote_id = :id 
             ORDER BY criterio ASC",
            ['id' => $conteoLoteId]
        );
    }
}