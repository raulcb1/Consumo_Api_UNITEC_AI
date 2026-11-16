<?php
// =====================================================================
// reportes.php - Endpoint AJAX del módulo de reportes
// =====================================================================
// Ubicación: public/api/reportes.php
//
// Acciones:
//   catalogo                       (GET)
//   definicion  ?codigo=X          (GET)
//   ejecutar    ?codigo=X&...      (GET)
//   exportar    ?codigo=X&...      (GET)   → descarga CSV
//   guardar_config                 (POST)
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/reportes_controller.php';

$accion = $_GET['accion'] ?? '';
$codigo = $_GET['codigo'] ?? '';

try {
    $controller = new ReportesController();

    switch ($accion) {
        case 'catalogo':
            $controller->catalogo();
            break;
        case 'definicion':
            $controller->definicion($codigo);
            break;
        case 'ejecutar':
            $controller->ejecutar($codigo);
            break;
        case 'exportar':
            $controller->exportarCsv($codigo);
            break;
        case 'guardar_config':
            $controller->guardarConfig();
            break;
        case 'evolucion_lote':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $controller->evolucionLote($id);
            break;
        default:
            Response::error('Acción no válida.', 400);
    }
} catch (Exception $e) {
    Response::error('Error interno: ' . $e->getMessage(), 500);
}
