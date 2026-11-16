<?php
require_once __DIR__ . '/../core/modelo.php';

class ClaseDiccionario extends Modelo
{
    protected $tabla = 'clases_diccionario';

    public function obtenerPorCodigo(string $codigo)
    {
        return $this->consultarUna(
            "SELECT * FROM clases_diccionario 
             WHERE codigo = :codigo AND deleted_at IS NULL",
            ['codigo' => $codigo]
        );
    }

    public function listarActivas(): array
    {
        return $this->consultar(
            "SELECT * FROM clases_diccionario 
             WHERE deleted_at IS NULL AND activo = 1 
             ORDER BY orden ASC, codigo ASC"
        );
    }

    public function listarTodo(): array
    {
        return $this->consultar(
            "SELECT * FROM clases_diccionario 
             WHERE deleted_at IS NULL 
             ORDER BY orden ASC, codigo ASC"
        );
    }

    /**
     * Devuelve las clases que aparecen en los datos sincronizados
     * pero aún NO están definidas en el diccionario.
     * Incluye estadísticas agregadas para ayudar al admin a priorizar.
     */
    public function listarFaltantes(): array
    {
        $sql = "SELECT 
                    dc.nombre_clase AS codigo,
                    COUNT(DISTINCT cl.lote_id) AS total_lotes,
                    COUNT(DISTINCT dc.conteo_lote_id) AS total_snapshots,
                    SUM(dc.total_frutas) AS total_frutas,
                    SUM(dc.peso_total_kg) AS total_kg,
                    MIN(cl.consultado_en) AS primera_aparicion,
                    MAX(cl.consultado_en) AS ultima_aparicion
                FROM detalles_clase dc
                INNER JOIN conteos_lote cl ON cl.id = dc.conteo_lote_id
                WHERE dc.nombre_clase NOT IN (
                    SELECT codigo FROM clases_diccionario WHERE deleted_at IS NULL
                )
                GROUP BY dc.nombre_clase
                ORDER BY total_kg DESC";

        return $this->consultar($sql);
    }

    /**
     * Cuenta rápida de clases faltantes (para badges en el sidebar).
     */
    public function contarFaltantes(): int
    {
        $fila = $this->consultarUna(
            "SELECT COUNT(DISTINCT dc.nombre_clase) AS total
             FROM detalles_clase dc
             WHERE dc.nombre_clase NOT IN (
                SELECT codigo FROM clases_diccionario WHERE deleted_at IS NULL
             )"
        );
        return (int) ($fila['total'] ?? 0);
    }
}