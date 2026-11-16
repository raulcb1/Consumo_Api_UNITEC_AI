<?php
// =====================================================================
// motor_reportes.php - Motor de ejecución de reportes
// =====================================================================
// Ubicación: app/core/motor_reportes.php
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class MotorReportes
{
    private array $catalogo;
    private PDO $db;

    public function __construct()
    {
        $this->catalogo = require __DIR__ . '/../config/reportes_predefinidos.php';
        $this->db = Database::getConexion();
    }

    /**
     * Devuelve el catálogo completo (sin el SQL por seguridad).
     */
    public function listarCatalogo(): array
    {
        $resultado = [];
        foreach ($this->catalogo as $codigo => $def) {
            $resultado[] = [
                'codigo'      => $codigo,
                'nombre'      => $def['nombre'],
                'descripcion' => $def['descripcion'],
                'categoria'   => $def['categoria'],
                'icono'       => $def['icono'] ?? 'reportes',
                'orden'       => $def['orden'] ?? 100,
                'columnas'    => $this->listarColumnasDisponibles($codigo),
                'filtros'     => $this->listarFiltrosDisponibles($codigo),
            ];
        }
        usort($resultado, fn($a, $b) => $a['orden'] <=> $b['orden']);
        return $resultado;
    }

    public function obtenerDefinicion(string $codigo): ?array
    {
        if (!isset($this->catalogo[$codigo])) return null;

        $def = $this->catalogo[$codigo];

        return [
            'codigo'      => $codigo,
            'nombre'      => $def['nombre'],
            'descripcion' => $def['descripcion'] ?? '',
            'categoria'   => $def['categoria'] ?? '',
            'icono'       => $def['icono'] ?? 'reportes',
            'orden'       => $def['orden'] ?? 100,
            'columnas'    => $this->listarColumnasDisponibles($codigo),
            'filtros'     => $this->listarFiltrosDisponibles($codigo),
            'grafico'     => $def['grafico'] ?? null, // <-- NUEVO
            'orden_default' => $def['orden_default'] ?? [],
            'limite_default' => $def['limite_default'] ?? 500,
        ];
    }

    public function existeReporte(string $codigo): bool
    {
        return isset($this->catalogo[$codigo]);
    }

    /**
     * Devuelve las columnas disponibles con sus opciones para la UI.
     */
    public function listarColumnasDisponibles(string $codigo): array
    {
        if (!isset($this->catalogo[$codigo])) return [];
        $cols = $this->catalogo[$codigo]['columnas'] ?? [];
        uasort($cols, fn($a, $b) => ($a['orden'] ?? 100) <=> ($b['orden'] ?? 100));
        $res = [];
        foreach ($cols as $id => $c) {
            $res[] = [
                'codigo'          => $id,
                'etiqueta'        => $c['etiqueta'] ?? $id,
                'tipo'            => $c['tipo'] ?? 'texto',
                'ancho'           => $c['ancho'] ?? 120,
                'visible_default' => $c['visible_default'] ?? true,
                'es_calculada'    => !empty($c['formula']),
            ];
        }
        return $res;
    }

    /**
     * Devuelve los filtros disponibles con sus opciones.
     */
    public function listarFiltrosDisponibles(string $codigo): array
    {
        if (!isset($this->catalogo[$codigo])) return [];
        $filtros = $this->catalogo[$codigo]['filtros'] ?? [];
        $res = [];
        foreach ($filtros as $id => $f) {
            $item = [
                'codigo'     => $id,
                'etiqueta'   => $f['etiqueta'] ?? $id,
                'tipo'       => $f['tipo'] ?? 'texto',
                'requerido'  => $f['requerido'] ?? false,
            ];

            // Si es select con SQL dinámico, traer las opciones
            if (!empty($f['opciones_sql'])) {
                $stmt = $this->db->query($f['opciones_sql']);
                $item['opciones'] = $stmt->fetchAll();
            }
            $res[] = $item;
        }
        return $res;
    }

    /**
     * Ejecuta un reporte con filtros y columnas dadas.
     *
     * @param string $codigo        Código del reporte
     * @param array  $filtros       Filtros activos (['linea_id' => 3, 'fecha_inicio' => '...'])
     * @param array  $columnasVis   Códigos de columnas visibles (vacío = default)
     * @param array  $ordenacion    ['columna' => 'fecha_inicio', 'direccion' => 'DESC']
     * @param int    $limite        Máximo de filas
     * @return array
     */
    public function ejecutar(
        string $codigo,
        array $filtros = [],
        array $columnasVis = [],
        array $ordenacion = [],
        int $limite = 0
    ): array {
        if (!isset($this->catalogo[$codigo])) {
            throw new Exception("Reporte '$codigo' no existe.");
        }

        $def = $this->catalogo[$codigo];

        // 1. Construir WHERE a partir de los filtros
        list($whereSql, $params) = $this->construirWhere($def, $filtros);

        // 2. Construir ORDER BY
        $orderSql = $this->construirOrderBy($def, $ordenacion);

        // 3. Construir LIMIT
        $limiteFinal = $limite > 0 ? $limite : ($def['limite_default'] ?? 500);
        $limiteFinal = min($limiteFinal, 5000); // tope duro

        // 4. SQL final
        $sql = $def['sql'] . $whereSql . $orderSql . " LIMIT " . (int) $limiteFinal;

        // 5. Ejecutar
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll();

        // 6. Determinar columnas visibles
        $columnasDisponibles = $def['columnas'];
        $columnasFinales = $this->resolverColumnasVisibles($columnasDisponibles, $columnasVis);

        // 7. Aplicar columnas calculadas
        $filas = $this->aplicarColumnasCalculadas($filas, $columnasFinales);

        // 8. Calcular totales para columnas numéricas
        $totales = $this->calcularTotales($filas, $columnasFinales);

        return [
            'reporte'  => $codigo,
            'columnas' => $columnasFinales,
            'filas'    => $filas,
            'totales'  => $totales,
            'meta'     => [
                'total_filas' => count($filas),
                'limite'      => $limiteFinal,
                'ejecutado_en' => date('Y-m-d H:i:s'),
            ]
        ];
    }

    // =================================================================
    // Helpers internos
    // =================================================================

    private function construirWhere(array $def, array $filtros): array
    {
        $condiciones = [];
        $params = [];

        foreach ($def['filtros'] ?? [] as $idFiltro => $fdef) {
            $valor = $filtros[$idFiltro] ?? null;

            // Filtros booleanos con SQL extra
            if (($fdef['tipo'] ?? '') === 'booleano') {
                if (!empty($valor) && !empty($fdef['sql_extra'])) {
                    $condiciones[] = '(' . $fdef['sql_extra'] . ')';
                }
                continue;
            }

            if ($valor === null || $valor === '') continue;

            $campo = $fdef['campo_sql'] ?? null;
            if (!$campo) continue;

            $operador = $fdef['operador'] ?? '=';
            $tipo = $fdef['tipo'] ?? 'texto';

            switch ($operador) {
                case 'LIKE':
                    $condiciones[] = "$campo LIKE :$idFiltro";
                    $params[$idFiltro] = '%' . $valor . '%';
                    break;

                case '>=':
                case '>':
                case '<=':
                case '<':
                case '=':
                case '!=':
                    $condiciones[] = "$campo $operador :$idFiltro";
                    $params[$idFiltro] = $valor;
                    break;

                case 'IN':
                    if (is_array($valor) && count($valor) > 0) {
                        $ph = [];
                        foreach ($valor as $i => $v) {
                            $k = $idFiltro . '_' . $i;
                            $ph[] = ":$k";
                            $params[$k] = $v;
                        }
                        $condiciones[] = "$campo IN (" . implode(',', $ph) . ")";
                    }
                    break;

                case 'BETWEEN':
                    if (is_array($valor) && count($valor) === 2) {
                        $condiciones[] = "$campo BETWEEN :{$idFiltro}_d AND :{$idFiltro}_h";
                        $params["{$idFiltro}_d"] = $valor[0];
                        $params["{$idFiltro}_h"] = $valor[1];
                    }
                    break;
            }
        }

        $where = count($condiciones) ? ' AND ' . implode(' AND ', $condiciones) : '';
        return [$where, $params];
    }

    private function construirOrderBy(array $def, array $ordenacion): string
    {
        if (empty($ordenacion['columna'])) {
            $ordenacion = $def['orden_default'] ?? [];
            if (empty($ordenacion)) return '';
            $col = array_key_first($ordenacion);
            $dir = strtoupper($ordenacion[$col]) === 'ASC' ? 'ASC' : 'DESC';
        } else {
            $col = $ordenacion['columna'];
            $dir = strtoupper($ordenacion['direccion'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        }

        // Validar que la columna exista en el reporte
        if (!isset($def['columnas'][$col])) return '';
        return " ORDER BY $col $dir";
    }

    private function resolverColumnasVisibles(array $disponibles, array $visibles): array
    {
        // Si no se especifica, usar todas las visibles por defecto
        if (empty($visibles)) {
            $visibles = [];
            foreach ($disponibles as $cod => $c) {
                if ($c['visible_default'] ?? true) $visibles[] = $cod;
            }
        }

        $res = [];
        foreach ($disponibles as $cod => $c) {
            if (!in_array($cod, $visibles, true)) continue;
            $res[] = [
                'codigo'       => $cod,
                'etiqueta'     => $c['etiqueta'] ?? $cod,
                'tipo'         => $c['tipo'] ?? 'texto',
                'ancho'        => $c['ancho'] ?? 120,
                'con_hora'     => $c['con_hora'] ?? false,
                'formato_miles' => $c['formato_miles'] ?? false,
                'es_calculada' => !empty($c['formula']),
            ];
        }
        return $res;
    }

    private function aplicarColumnasCalculadas(array $filas, array $columnas): array
    {
        $calculadas = array_filter($columnas, fn($c) => !empty($c['es_calculada']));

        foreach ($filas as &$fila) {
            foreach ($calculadas as $col) {
                $cod = $col['codigo'];
                switch ($cod) {
                    case 'porcentaje_merma':
                        // Fórmula para reporte de pallets
                        $pesoDecl = $this->aNumero($fila['peso_declarado'] ?? 0);
                        $kgProc   = $this->aNumero($fila['kg_procesados'] ?? 0);
                        $kgDes    = $this->aNumero($fila['kg_desecho'] ?? 0);
                        $fila[$cod] = $pesoDecl > 0
                            ? round((($pesoDecl - $kgProc - $kgDes) / $pesoDecl) * 100, 2)
                            : 0;
                        break;

                    case 'porcentaje_merma_variedad':
                        // Fórmula para reporte agrupado por variedad
                        $kgProc = $this->aNumero($fila['kg_procesados'] ?? 0);
                        $kgDes  = $this->aNumero($fila['kg_desecho'] ?? 0);
                        $total  = $kgProc + $kgDes;
                        $fila[$cod] = $total > 0 ? round(($kgDes / $total) * 100, 2) : 0;
                        break;

                    case 'suma_kg_totales':
                        // Kg procesados + Kg desecho
                        $kgProc = $this->aNumero($fila['kg_procesados'] ?? 0);
                        $kgDes  = $this->aNumero($fila['kg_desecho'] ?? 0);
                        $fila[$cod] = round($kgProc + $kgDes, 2);
                        break;

                    case 'estado_lote':
                        $fila[$cod] = !empty($fila['activo']) ? 'Activo' : 'Finalizado';
                        break;
                }
            }
        }
        unset($fila);
        return $filas;
    }

    private function calcularTotales(array $filas, array $columnas): array
    {
        $totales = [];
        foreach ($columnas as $c) {
            $tipo = $c['tipo'] ?? 'texto';
            if (!in_array($tipo, ['entero', 'decimal'], true)) continue;

            $suma = 0;
            $count = 0;
            foreach ($filas as $f) {
                $v = $f[$c['codigo']] ?? null;
                if ($v === null || $v === '') continue;
                $suma += $this->aNumero($v);
                $count++;
            }
            if ($count > 0) {
                $totales[$c['codigo']] = round($suma, 2);
            }
        }
        return $totales;
    }

    private function aNumero($valor): float
    {
        if (is_numeric($valor)) return (float) $valor;
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $valor);
    }
}
