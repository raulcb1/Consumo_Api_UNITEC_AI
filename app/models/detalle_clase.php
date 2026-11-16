<?php
require_once __DIR__ . '/../core/modelo.php';

class DetalleClase extends Modelo
{
    protected $tabla = 'detalles_clase';

    public function listarPorConteo(int $conteoLoteId): array
    {
        return $this->consultar(
            "SELECT * FROM detalles_clase 
             WHERE conteo_lote_id = :id 
             ORDER BY tipo_programa ASC, nombre_clase ASC",
            ['id' => $conteoLoteId]
        );
    }

    public function listarPorConteoYTipo(int $conteoLoteId, string $tipoPrograma): array
    {
        return $this->consultar(
            "SELECT * FROM detalles_clase 
             WHERE conteo_lote_id = :id AND tipo_programa = :tipo
             ORDER BY nombre_clase ASC",
            ['id' => $conteoLoteId, 'tipo' => $tipoPrograma]
        );
    }

    /**
     * Obtener detalles contables de un conteo.
     */
    /*
    public function obtenerContabilidad(int $lot_class_count_id): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE lot_class_count_id = :lot_class_count_id AND tipo_programa = 'accounting'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lot_class_count_id' => $lot_class_count_id]);
        return $stmt->fetchAll();
    }*/
}