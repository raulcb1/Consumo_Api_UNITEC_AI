<?php
require_once __DIR__ . '/../core/modelo.php';

class PromedioClase extends Modelo
{
    protected $tabla = 'promedios_clase';

    public function listarPorDetalle(int $detalleClaseId): array
    {
        return $this->consultar(
            "SELECT * FROM promedios_clase 
             WHERE detalle_clase_id = :id 
             ORDER BY criterio ASC",
            ['id' => $detalleClaseId]
        );
    }
}