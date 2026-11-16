<?php
// app/controllers/exportarcontroller.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../config/database.php';

// =====================================================================
// IMPORTANTE: Asegúrate de que la ruta de PhpSpreadsheet sea la correcta.
// Si lo instalaste con composer, cambia esto por: __DIR__ . '/../../vendor/autoload.php';
// =====================================================================
require_once __DIR__ . '/../../public/assets/libs/phpspreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExportarController
{
    public function __construct()
    {
        Auth::requiereSesion();
    }

    public function reporteCalidad(int $loteId): void
    {
        $db = Database::getConexion();

        // -----------------------------------------------------------------
        // 1. OBTENER DATOS DE LA BASE DE DATOS
        // -----------------------------------------------------------------

        // 1.1. Datos generales del lote
        $stmt = $db->prepare("SELECT * FROM lotes WHERE id = :id");
        $stmt->execute(['id' => $loteId]);
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lote) die("Lote no encontrado.");

        // 1.2. Último conteo (para Brix y Acidez)
        $stmt = $db->prepare("SELECT * FROM conteos_lote WHERE lote_id = :id ORDER BY id DESC LIMIT 1");
        $stmt->execute(['id' => $loteId]);
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
        $conteoId = $conteo['id'] ?? 0;

        // 1.3. Clases finales (BUENO, CAT2, etc.)
        $stmt = $db->prepare("SELECT * FROM detalles_clase WHERE conteo_lote_id = :cid");
        $stmt->execute(['cid' => $conteoId]);
        $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 1.4. Medidas y Defectos (Calibres, Colores, Defectos)
        $stmt = $db->prepare("SELECT * FROM distribuciones_lote WHERE conteo_lote_id = :cid ORDER BY porcentaje DESC");
        $stmt->execute(['cid' => $conteoId]);
        $distribuciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 1.5. Agrupar las distribuciones por su "criterio" para llenar las tablas
        $agrupado = [];
        foreach ($distribuciones as $d) {
            $agrupado[$d['criterio']][] = $d;
        }
        // $agrupado['AvgDiameter'] -> Calibres
        // $agrupado['Defects']     -> Defectos individuales
        // $agrupado['PercColor']   -> Calidad global (PRIMERO/CAT2)
        // $agrupado['Weight']      -> Colores

        // -----------------------------------------------------------------
        // 2. CARGAR LA PLANTILLA EXCEL
        // -----------------------------------------------------------------
        $rutaPlantilla = __DIR__ . '/../../public/templates/plantilla_reporte.xlsx';
        if (!file_exists($rutaPlantilla)) die("Error: No se encontró la plantilla en $rutaPlantilla");

        $spreadsheet = IOFactory::load($rutaPlantilla);
        $sheet = $spreadsheet->getActiveSheet();

        // -----------------------------------------------------------------
        // 3. REEMPLAZAR DATOS ESTÁTICOS (Marcadores únicos)
        // -----------------------------------------------------------------
        // Busca en toda la hoja los marcadores y los reemplaza por los datos.
        $reemplazos = [
            '{{ID_API}}' => $lote['lot_id_api'],
            '{{NOMBRE_LOTE}}' => $lote['nombre_lote'],
            '{{PROGRAMA}}' => $lote['programa'] ?? '—',
            '{{VARIEDAD}}' => $lote['descripcion_variedad'] ?? '—',
            '{{FECHA_INICIO}}' => $lote['fecha_inicio'],
            '{{FECHA_FIN}}' => $lote['fecha_fin'],
            '{{CAJAS}}' => $lote['paquetes'],
            '{{BRIX_PROMEDIO}}' => $conteo['brix_promedio'] ?? '—',
            '{{BRIX_UMBRAL}}' => $conteo['brix_bajo_umbral'] ?? '—',
            '{{ACIDEZ_PROMEDIO}}' => $conteo['acidez_promedio'] ?? '—',
            '{{ACIDEZ_UMBRAL}}' => $conteo['acidez_alta_umbral'] ?? '—',
        ];

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $val = $cell->getValue();
                if (is_string($val)) {
                    $nuevoValor = $val;
                    foreach ($reemplazos as $marcador => $dato) {
                        if (strpos($nuevoValor, $marcador) !== false) {
                            $nuevoValor = str_replace($marcador, $dato, $nuevoValor);
                        }
                    }
                    if ($nuevoValor !== $val) $cell->setValue($nuevoValor);
                }
            }
        }

        // -----------------------------------------------------------------
        // 4. LLENAR TABLAS DINÁMICAS
        // -----------------------------------------------------------------
        // Llamamos a la función maestra para cada tabla.
        // Le pasamos: La hoja, el marcador, los datos, y los nombres de columna de la BD.

        // Tabla de Clases Finales (BUENO, CAT2)
        $this->procesarTabla($sheet, '{{CLASE_NOMBRE}}', $clases, 'nombre_clase', 'porcentaje_clase');

        // Tabla de Calibres
        $this->procesarTabla($sheet, '{{CALIBRE_RANGO}}', $agrupado['AvgDiameter'] ?? [], 'rango', 'porcentaje');

        // Tabla de Defectos (CAT2)
        $this->procesarTabla($sheet, '{{DEFECTO_RANGO}}', $agrupado['Defects'] ?? [], 'rango', 'porcentaje');

        // Tabla de Calidad Global (PRIMERO vs CAT2)
        $this->procesarTabla($sheet, '{{CALIDAD_RANGO}}', $agrupado['PercColor'] ?? [], 'rango', 'porcentaje');

        // Tabla de Colores Visuales
        $this->procesarTabla($sheet, '{{COLOR_RANGO}}', $agrupado['Weight'] ?? [], 'rango', 'porcentaje');

        // -----------------------------------------------------------------
        // 5. GENERAR DESCARGA
        // -----------------------------------------------------------------
        $nombreArchivo = 'Reporte_Calidad_' . $lote['lot_id_api'] . '.xlsx';

        // Limpiamos el buffer de salida para evitar que Excel se descargue corrupto (por espacios en blanco en PHP)
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /**
     * FUNCIÓN MAESTRA: Busca el marcador, inserta las filas necesarias,
     * llena los datos y limpia las filas de ejemplo.
     * 
     * @param $sheet         El objeto de la hoja de Excel
     * @param $marcador      El texto a buscar (ej. {{CALIBRE_RANGO}})
     * @param $datos         Array de datos traído de la base de datos
     * @param $colNombre     La propiedad del array que tiene el nombre (ej. 'rango')
     * @param $colValor      La propiedad del array que tiene el porcentaje (ej. 'porcentaje')
     */
    private function procesarTabla($sheet, $marcador, $datos, $colNombre, $colValor)
    {
        // 1. Buscar la celda exacta donde está el marcador
        $celdaInicio = null;
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $val = $cell->getValue();
                if (is_string($val) && strpos($val, $marcador) !== false) {
                    $celdaInicio = $cell->getCoordinate(); // Ej: 'C32'
                    break 2;
                }
            }
        }

        // Si el marcador no existe en la plantilla, salimos (por si olvidaste ponerlo)
        if (!$celdaInicio) return;

        // 2. Calcular coordenadas de inicio
        $filaInicio = (int) preg_replace('/[^0-9]/', '', $celdaInicio); // Ej: 32
        $columnaLetra = preg_replace('/[^A-Z]/', '', $celdaInicio);     // Ej: 'C'
        $columnaNum = Coordinate::columnIndexFromString($columnaLetra);
        $cantidadDatos = count($datos);

        // 3. Capturar el estilo de la fila del marcador ANTES de modificar nada
        $estiloCeldaNombre = $sheet->getStyle($columnaLetra . $filaInicio);
        $estiloCeldaValor = $sheet->getStyle(Coordinate::stringFromColumnIndex($columnaNum + 1) . $filaInicio);

        // 3. Borrar el texto del marcador original de inmediato
        $sheet->setCellValue($columnaLetra . $filaInicio, '');
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnaNum + 1) . $filaInicio, '');

        // 4. Detectar cuántas filas de ejemplo hay en la plantilla antes del siguiente título
        $filaFinTabla = $filaInicio + 1;
        $textosTitulos = ['calibre', 'defecto', 'calidad', 'color', 'medidas', 'clases'];
        while (true) {
            $valC = $sheet->getCell($columnaLetra . $filaFinTabla)->getValue();
            if (is_string($valC)) {
                $texto = strtolower(trim($valC));
                $esTitulo = false;
                foreach ($textosTitulos as $palabra) {
                    if (strpos($texto, $palabra) !== false) {
                        $esTitulo = true;
                        break;
                    }
                }
                if ($esTitulo) break; // Llegamos al siguiente cuadro
            }
            // Si la fila está vacía, paramos
            $valD = $sheet->getCell(Coordinate::stringFromColumnIndex($columnaNum + 1) . $filaFinTabla)->getValue();
            if ($valC === null && $valD === null) break;

            $filaFinTabla++;
            if ($filaFinTabla > $filaInicio + 100) break; // Seguro contra bucles infinitos
        }

        $filasDisponibles = $filaFinTabla - $filaInicio;
        $filasAInsertar = 0;

        // 5. Si hay más datos que filas disponibles, insertamos filas al final de la tabla
        if ($cantidadDatos > $filasDisponibles) {
            $filasAInsertar = $cantidadDatos - $filasDisponibles;
            $sheet->insertNewRowBefore($filaFinTabla, $filasAInsertar);
        }

        // 5. Escribir los datos y aplicar estilos
        $filaActual = $filaInicio;
        foreach ($datos as $item) {
            // Escribir nombre (ej. 'XL')
            $sheet->setCellValue($columnaLetra . $filaActual, $item[$colNombre]);
            // Escribir valor (ej. '53.20')
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnaNum + 1) . $filaActual, $item[$colValor]);

            // Duplicar el estilo de la plantilla a esta nueva fila
            $sheet->duplicateStyle($estiloCeldaNombre, $columnaLetra . $filaActual);
            $sheet->duplicateStyle($estiloCeldaValor, Coordinate::stringFromColumnIndex($columnaNum + 1) . $filaActual);

            $filaActual++;
        }

        // 6. Limpiar las filas de ejemplo estáticas de la plantilla
        // Como insertamos filas arriba, los textos de ejemplo que estaban en la plantilla
        // (como "J", "XL", "CAT2") han sido empujados hacia abajo.
        // Este bucle los busca y los borra hasta encontrar el siguiente título de sección.
        $filaLimpieza = $filaInicio + $cantidadDatos;

        while (true) {
            // Obtenemos el valor de la columna C (o donde estén los nombres)
            $valC = $sheet->getCell($columnaLetra . $filaLimpieza)->getValue();
            // Obtenemos el valor de la columna D (o donde estén los porcentajes)
            $valD = $sheet->getCell(Coordinate::stringFromColumnIndex($columnaNum + 1) . $filaLimpieza)->getValue();

            // Si encontramos el título de la siguiente sección, detenemos la limpieza
            if (is_string($valC)) {
                $texto = strtolower(trim($valC));
                if (
                    strpos($texto, 'calibre') !== false ||
                    strpos($texto, 'defecto') !== false ||
                    strpos($texto, 'calidad') !== false ||
                    strpos($texto, 'color') !== false ||
                    strpos($texto, 'medidas') !== false
                ) {
                    break; // Llegamos al siguiente cuadro, no borramos más.
                }
            }

            // Si ambas celdas están vacías, asumimos que es el final natural de la tabla
            if ($valC === null && $valD === null) {
                break;
            }

            // Borramos el texto de ejemplo
            $sheet->setCellValue($columnaLetra . $filaLimpieza, '');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnaNum + 1) . $filaLimpieza, '');

            $filaLimpieza++;

            // Medida de seguridad para evitar un bucle infinito si hay un error en la plantilla
            if ($filaLimpieza > $filaInicio + 100) break;
        }
    }
}
