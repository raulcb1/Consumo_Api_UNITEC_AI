<?php
require_once __DIR__ . '/../core/modelo.php';

class Lote extends Modelo
{
    protected $tabla = 'lotes';

    /**
     * Campos que se consideran clave para que un lote esté "completo".
     * Estructura: 'columna_bd' => 'Etiqueta legible'
     */
    const CAMPOS_CLAVE = [
        'codigo_externo'        => 'Código externo',
        'descripcion_productor' => 'Productor',
        'descripcion_variedad'  => 'Variedad',
        'peso'                  => 'Peso',
    ];


    public function listarPorLinea(int $lineaId, bool $soloConProcesamientoActual = false): array
    {
        $sql = "SELECT l.*, 
                       (SELECT COUNT(*) FROM procesamientos_lotes pl 
                        WHERE pl.lote_id = l.id AND pl.es_actual = 1) AS tiene_procesamiento_actual
                FROM lotes l
                WHERE l.linea_id = :linea_id AND l.deleted_at IS NULL";
        if ($soloConProcesamientoActual) {
            $sql .= " AND EXISTS (SELECT 1 FROM procesamientos_lotes pl 
                                  WHERE pl.lote_id = l.id AND pl.es_actual = 1)";
        }
        $sql .= " ORDER BY l.created_at DESC";
        return $this->consultar($sql, ['linea_id' => $lineaId]);
    }

    public function obtenerPorNombreYLinea(string $nombre, int $lineaId)
    {
        return $this->consultarUna(
            "SELECT * FROM lotes 
             WHERE nombre_lote = :nombre AND linea_id = :linea_id AND deleted_at IS NULL",
            ['nombre' => $nombre, 'linea_id' => $lineaId]
        );
    }

    public function obtenerPorCodigoExterno(string $codigoExterno, int $lineaId)
    {
        return $this->consultarUna(
            "SELECT * FROM lotes 
             WHERE codigo_externo = :codigo AND linea_id = :linea_id AND deleted_at IS NULL",
            ['codigo' => $codigoExterno, 'linea_id' => $lineaId]
        );
    }

    public function listarConFiltros(int $lineaId, array $filtros = []): array
    {
        $sql = "SELECT * FROM lotes WHERE linea_id = :linea_id AND deleted_at IS NULL";
        $params = ['linea_id' => $lineaId];

        if (!empty($filtros['codigo_externo'])) {
            $sql .= " AND codigo_externo = :codigo_externo";
            $params['codigo_externo'] = $filtros['codigo_externo'];
        }
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND fecha_inicio >= :fecha_inicio";
            $params['fecha_inicio'] = $filtros['fecha_inicio'];
        }
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND fecha_fin <= :fecha_fin";
            $params['fecha_fin'] = $filtros['fecha_fin'];
        }
        $sql .= " ORDER BY created_at DESC";
        return $this->consultar($sql, $params);
    }

    /**
     * Listado general con filtros opcionales.
     * Si $lineaId = 0, lista de todas las líneas.
     */

    /*
    public function listarGeneral(int $lineaId = 0, array $filtros = [], bool $soloActual = false): array
    {
        $sql = "SELECT l.*,
                       (SELECT COUNT(*) FROM procesamientos_lotes pl 
                        WHERE pl.lote_id = l.id AND pl.es_actual = 1) AS tiene_procesamiento_actual
                FROM lotes l
                WHERE l.deleted_at IS NULL";
        $params = [];

        if ($lineaId > 0) {
            $sql .= " AND l.linea_id = :linea_id";
            $params['linea_id'] = $lineaId;
        }

        if (!empty($filtros['codigo_externo'])) {
            $sql .= " AND l.codigo_externo LIKE :codigo_externo";
            $params['codigo_externo'] = '%' . $filtros['codigo_externo'] . '%';
        }
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND l.fecha_inicio >= :fecha_inicio";
            $params['fecha_inicio'] = $filtros['fecha_inicio'];
        }
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND l.fecha_fin <= :fecha_fin";
            $params['fecha_fin'] = $filtros['fecha_fin'];
        }
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (l.nombre_lote LIKE :buscar 
                          OR l.codigo_externo LIKE :buscar 
                          OR l.descripcion_variedad LIKE :buscar 
                          OR l.descripcion_productor LIKE :buscar)";
            $params['buscar'] = '%' . $filtros['buscar'] . '%';
        }

        if ($soloActual) {
            $sql .= " AND EXISTS (SELECT 1 FROM procesamientos_lotes pl 
                                  WHERE pl.lote_id = l.id AND pl.es_actual = 1)";
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT 300";
        return $this->consultar($sql, $params);
    }
        */

    /**
     * Devuelve un lote con todos sus datos, incluyendo línea y procesamientos.
     */
    public function obtenerCompleto(int $id): ?array
    {
        $lote = $this->consultarUna(
            "SELECT l.*, 
                    ln.linea AS linea_codigo, 
                    ln.descripcion AS linea_descripcion, 
                    ln.planta AS linea_planta
             FROM lotes l
             LEFT JOIN lineas ln ON ln.id = l.linea_id
             WHERE l.id = :id AND l.deleted_at IS NULL",
            ['id' => $id]
        );

        if (!$lote) {
            return null;
        }

        $lote['procesamientos'] = $this->consultar(
            "SELECT * FROM procesamientos_lotes 
             WHERE lote_id = :id 
             ORDER BY created_at DESC",
            ['id' => $id]
        );

        return $lote;
    }

    /**
     * Listado general con filtros opcionales y detección de campos faltantes.
     */
    public function listarGeneral(int $lineaId = 0, array $filtros = [], bool $soloActual = false): array
    {
        $sql = "SELECT l.*,
                       (SELECT COUNT(*) FROM procesamientos_lotes pl 
                        WHERE pl.lote_id = l.id AND pl.es_actual = 1) AS tiene_procesamiento_actual
                FROM lotes l
                WHERE l.deleted_at IS NULL";
        $params = [];

        if ($lineaId > 0) {
            $sql .= " AND l.linea_id = :linea_id";
            $params['linea_id'] = $lineaId;
        }

        if (!empty($filtros['codigo_externo'])) {
            $sql .= " AND l.codigo_externo LIKE :codigo_externo";
            $params['codigo_externo'] = '%' . $filtros['codigo_externo'] . '%';
        }
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND l.fecha_inicio >= :fecha_inicio";
            $params['fecha_inicio'] = $filtros['fecha_inicio'];
        }
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND l.fecha_fin <= :fecha_fin";
            $params['fecha_fin'] = $filtros['fecha_fin'];
        }
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (l.nombre_lote LIKE :buscar 
                          OR l.codigo_externo LIKE :buscar 
                          OR l.descripcion_variedad LIKE :buscar 
                          OR l.descripcion_productor LIKE :buscar)";
            $params['buscar'] = '%' . $filtros['buscar'] . '%';
        }

        // Filtro: solo lotes con campos incompletos
        if (!empty($filtros['solo_incompletos'])) {
            $condiciones = [];
            foreach (array_keys(self::CAMPOS_CLAVE) as $campo) {
                $condiciones[] = "(l.$campo IS NULL OR l.$campo = '')";
            }
            $sql .= " AND (" . implode(' OR ', $condiciones) . ")";
        }

        if ($soloActual) {
            $sql .= " AND EXISTS (SELECT 1 FROM procesamientos_lotes pl 
                                  WHERE pl.lote_id = l.id AND pl.es_actual = 1)";
        }

        $sql .= " ORDER BY l.fecha_inicio DESC, l.id DESC LIMIT 300";
        $lotes = $this->consultar($sql, $params);

        // Calcular campos faltantes para cada lote
        foreach ($lotes as &$lote) {
            $lote['campos_faltantes'] = $this->detectarCamposFaltantes($lote);
        }
        unset($lote);

        return $lotes;
    }

    /**
     * Devuelve un array de etiquetas de los campos vacíos en el lote.
     */
    public function detectarCamposFaltantes(array $lote): array
    {
        $faltantes = [];
        foreach (self::CAMPOS_CLAVE as $campo => $etiqueta) {
            $valor = $lote[$campo] ?? null;
            if ($valor === null || trim((string) $valor) === '') {
                $faltantes[] = $etiqueta;
            }
        }
        return $faltantes;
    }

    public function obtenerPorLotIdApi(int $lotIdApi, int $lineaId, int $equipoId = 1)
    {
        return $this->consultarUna(
            "SELECT * FROM lotes WHERE lot_id_api = :lot_id_api AND linea_id = :linea_id AND equipo_id = :equipo_id AND deleted_at IS NULL",
            ['lot_id_api' => $lotIdApi, 'linea_id' => $lineaId, 'equipo_id' => $equipoId]
        );
    }
}
