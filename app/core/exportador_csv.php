<?php
// =====================================================================
// exportador_csv.php - Genera CSV a partir de un resultado de reporte
// =====================================================================
// Ubicación: app/core/exportador_csv.php
// =====================================================================

class ExportadorCsv
{
    /**
     * Envía un CSV al navegador.
     *
     * @param array  $resultado    Estructura devuelta por MotorReportes::ejecutar()
     * @param string $nombreArchivo Nombre sin extensión
     */
    public static function descargar(array $resultado, string $nombreArchivo = 'reporte'): void
    {
        $nombreArchivo = preg_replace('/[^a-z0-9_\-]/i', '_', $nombreArchivo);
        $nombreArchivo .= '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // BOM UTF-8 para Excel
        fwrite($out, "\xEF\xBB\xBF");

        // Separador: punto y coma para que Excel en español lo abra bien
        $sep = ';';

        // 1. Encabezado
        $encabezados = array_map(fn($c) => $c['etiqueta'], $resultado['columnas']);
        fputcsv($out, $encabezados, $sep);

        // 2. Filas
        foreach ($resultado['filas'] as $fila) {
            $linea = [];
            foreach ($resultado['columnas'] as $col) {
                $valor = $fila[$col['codigo']] ?? '';
                $linea[] = self::formatearValor($valor, $col);
            }
            fputcsv($out, $linea, $sep);
        }

        // 3. Totales (opcional)
        if (!empty($resultado['totales'])) {
            $lineaTotales = [];
            foreach ($resultado['columnas'] as $col) {
                $cod = $col['codigo'];
                if (isset($resultado['totales'][$cod])) {
                    $lineaTotales[] = 'TOTAL: ' . $resultado['totales'][$cod];
                } else {
                    $lineaTotales[] = '';
                }
            }
            fputcsv($out, $lineaTotales, $sep);
        }

        fclose($out);
        exit;
    }

    private static function formatearValor($valor, array $col): string
    {
        if ($valor === null) return '';

        $tipo = $col['tipo'] ?? 'texto';

        if ($tipo === 'fecha' && $valor) {
            try {
                $dt = new DateTime($valor);
                return !empty($col['con_hora'])
                    ? $dt->format('d/m/Y H:i')
                    : $dt->format('d/m/Y');
            } catch (Exception $e) {
                return (string) $valor;
            }
        }

        if ($tipo === 'decimal') {
            return number_format((float) $valor, 2, '.', '');
        }

        if ($tipo === 'entero' && !empty($col['formato_miles'])) {
            return number_format((int) $valor, 0, '.', ',');
        }

        return (string) $valor;
    }
}
