<?php
// =====================================================================
// panel.php - Endpoint AJAX del panel principal
// =====================================================================
// Ubicación: public/api/panel.php
//
// Acciones disponibles (query string ?accion=...):
//   resumen  (GET) - Devuelve el resumen general del sistema
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/panelcontroller.php';

// Determinar la acción solicitada
$accion = $_GET['accion'] ?? 'resumen';

try {
    $controller = new PanelController();

    switch ($accion) {
        case 'resumen':
            $controller->resumen();
            break;

        default:
            Response::error('Acción no válida.', 400);
    }
} catch (Exception $e) {
    Response::error('Error interno: ' . $e->getMessage(), 500);
}