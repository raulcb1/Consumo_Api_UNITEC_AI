<?php
// =====================================================================
// lineas.php - Endpoint AJAX para el módulo de líneas
// =====================================================================
// Ubicación: public/api/lineas.php
//
// Acciones:
//   listar     (GET)
//   obtener    (GET)   ?id=X
//   eliminar   (POST)  ?accion=eliminar&id=X  (soft delete local)
//   restaurar  (POST)  ?accion=restaurar&id=X
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/lineascontroller.php';

$accion = $_GET['accion'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $controller = new LineasController();

    if ($accion !== '') {
        switch ($accion) {
            case 'eliminar':
                $controller->eliminar($id);
                break;
            case 'restaurar':
                // Añade este método a LineasController si aún no lo tienes
                $controller->restaurar($id);
                break;
            case 'actualizar':
                $controller->actualizar($id);
                break;
            default:
                Response::error('Acción no válida.', 400);
        }
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->crear();
    } elseif ($id > 0) {
        $controller->obtener($id);
    } else {
        $controller->listar();
    }
} catch (Exception $e) {
    Response::error('Error interno: ' . $e->getMessage(), 500);
}
