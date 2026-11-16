<?php
// =====================================================================
// reportes_controller.php
// =====================================================================
// Ubicación: app/controllers/reportes_controller.php
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/motor_reportes.php';
require_once __DIR__ . '/../core/exportador_csv.php';
require_once __DIR__ . '/../models/reporte_usuario_config.php';

class ReportesController
{
    private MotorReportes $motor;
    private ReporteUsuarioConfig $configModel;

    public function __construct()
    {
        Auth::requiereSesion();
        $this->motor = new MotorReportes();
        $this->configModel = new ReporteUsuarioConfig();
    }

    // -----------------------------------------------------------------
    // CATÁLOGO - GET
    // -----------------------------------------------------------------
    public function catalogo(): void
    {
        if (!Auth::tienePermiso('reportes', 'ver')) {
            Response::error('No tienes permiso para ver reportes.', 403);
        }
        $catalogo = $this->motor->listarCatalogo();
        Response::exito($catalogo, 'Catálogo de reportes');
    }

    // -----------------------------------------------------------------
    // DEFINICIÓN DE UN REPORTE - GET
    // -----------------------------------------------------------------
    public function definicion(string $codigo): void
    {
        if (!Auth::tienePermiso('reportes', 'ver')) {
            Response::error('No tienes permiso para ver reportes.', 403);
        }
        $def = $this->motor->obtenerDefinicion($codigo);
        if (!$def) {
            Response::error('Reporte no encontrado.', 404);
        }

        // Añadir configuración del usuario si existe
        $usuario = Auth::usuarioActual();
        $config = $this->configModel->obtener($usuario['usuario_id'], $codigo);
        if ($config) {
            $def['config_usuario'] = [
                'columnas_visibles' => json_decode($config['columnas_visibles'] ?? '[]', true) ?: [],
                'columnas_orden'    => json_decode($config['columnas_orden'] ?? '[]', true) ?: [],
                'filtros_guardados' => json_decode($config['filtros_guardados'] ?? '[]', true) ?: [],
            ];
        }

        Response::exito($def, 'Definición del reporte');
    }

    // -----------------------------------------------------------------
    // EJECUTAR REPORTE - GET
    // -----------------------------------------------------------------
    public function ejecutar(string $codigo): void
    {
        if (!Auth::tienePermiso('reportes', 'ver')) {
            Response::error('No tienes permiso para ejecutar reportes.', 403);
        }

        if (!$this->motor->existeReporte($codigo)) {
            Response::error('Reporte no encontrado.', 404);
        }

        // Leer parámetros del query string
        $filtros = $_GET['filtros'] ?? [];
        if (is_string($filtros)) $filtros = json_decode($filtros, true) ?: [];

        $columnas = $_GET['columnas'] ?? [];
        if (is_string($columnas)) $columnas = json_decode($columnas, true) ?: [];

        $orden = [];
        if (!empty($_GET['orden_columna'])) {
            $orden = [
                'columna'   => $_GET['orden_columna'],
                'direccion' => $_GET['orden_dir'] ?? 'DESC',
            ];
        }

        $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 0;

        try {
            $resultado = $this->motor->ejecutar($codigo, $filtros, $columnas, $orden, $limite);
            Response::exito($resultado, 'Reporte ejecutado');
        } catch (Exception $e) {
            Response::error('Error al ejecutar el reporte: ' . $e->getMessage(), 500);
        }
    }

    // -----------------------------------------------------------------
    // EXPORTAR A CSV - GET
    // -----------------------------------------------------------------
    public function exportarCsv(string $codigo): void
    {
        if (!Auth::tienePermiso('reportes', 'exportar')) {
            Response::error('No tienes permiso para exportar reportes.', 403);
        }

        if (!$this->motor->existeReporte($codigo)) {
            Response::error('Reporte no encontrado.', 404);
        }

        $filtros = $_GET['filtros'] ?? [];
        if (is_string($filtros)) $filtros = json_decode($filtros, true) ?: [];

        $columnas = $_GET['columnas'] ?? [];
        if (is_string($columnas)) $columnas = json_decode($columnas, true) ?: [];

        $resultado = $this->motor->ejecutar($codigo, $filtros, $columnas, [], 5000);

        // Generar CSV (hace exit)
        ExportadorCsv::descargar($resultado, 'reporte_' . $codigo);
    }

    // -----------------------------------------------------------------
    // GUARDAR CONFIGURACIÓN DEL USUARIO - POST
    // -----------------------------------------------------------------
    public function guardarConfig(): void
    {
        if (!Auth::tienePermiso('reportes', 'ver')) {
            Response::error('No tienes permiso.', 403);
        }

        $datos = $this->leerJson();
        if (empty($datos['reporte_codigo'])) {
            Response::error('Falta el código del reporte.', 400);
        }

        $usuario = Auth::usuarioActual();
        $ok = $this->configModel->guardar($usuario['usuario_id'], $datos['reporte_codigo'], $datos);

        if (!$ok) {
            Response::error('No se pudo guardar la configuración.', 500);
        }
        Response::exito(null, 'Configuración guardada');
    }

    // -----------------------------------------------------------------
    private function leerJson(): array
    {
        $cuerpo = file_get_contents('php://input');
        $datos = json_decode($cuerpo, true);
        return is_array($datos) ? $datos : [];
    }

    public function evolucionLote(int $loteId): void
    {
        if (!Auth::tienePermiso('reportes', 'ver')) {
            Response::error('No tienes permiso.', 403);
        }

        $db = Database::getConexion();

        // Traemos todos los conteos de ese lote ordenados por fecha
        $sql = "SELECT cl.id AS conteo_id, cl.consultado_en, dc.nombre_clase, dc.total_frutas, dc.peso_total_kg
                FROM conteos_lote cl
                INNER JOIN detalles_clase dc ON dc.conteo_lote_id = cl.id
                WHERE cl.lote_id = :lote_id 
                  AND dc.tipo_programa = 'production'
                ORDER BY cl.consultado_en ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['lote_id' => $loteId]);
        $filas = $stmt->fetchAll();

        // Procesar datos para agrupar por Fecha (Snapshot) y luego por Clase
        $evolucion = [];
        foreach ($filas as $f) {
            $fecha = $f['consultado_en'];
            $clase = $f['nombre_clase'];

            if (!isset($evolucion[$fecha])) {
                $evolucion[$fecha] = [
                    'fecha' => $fecha,
                    'clases' => []
                ];
            }
            $evolucion[$fecha]['clases'][$clase] = [
                'frutas' => (int)$f['total_frutas'],
                'kg'     => (float)$f['peso_total_kg']
            ];
        }

        // Reordenar para el gráfico
        $fechas = array_keys($evolucion);
        sort($fechas);

        $nombresClases = [];
        foreach ($evolucion as $e) {
            foreach (array_keys($e['clases']) as $c) {
                $nombresClases[$c] = true;
            }
        }
        $nombresClases = array_keys($nombresClases);

        // Armar las series para ApexCharts
        $series = [];
        foreach ($nombresClases as $clase) {
            $data = [];
            foreach ($fechas as $f) {
                $data[] = isset($evolucion[$f]['clases'][$clase]) ? $evolucion[$f]['clases'][$clase]['kg'] : null;
            }
            $series[] = [
                'name' => $clase,
                'data' => $data
            ];
        }

        Response::exito([
            'fechas' => $fechas,
            'series' => $series
        ], 'Evolución del lote');
    }
}
