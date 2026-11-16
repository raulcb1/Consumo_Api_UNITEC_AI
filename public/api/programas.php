<?php
// =====================================================================
// programas.php - Endpoint AJAX para el módulo de programas
// =====================================================================
// Ubicación: public/api/programas.php
//
// Acciones:
//   listar    (GET)   ?linea_id=X (opcional)
//   obtener   (GET)   ?id=X
//   crear     (POST)  body JSON
//   actualizar (POST) ?accion=actualizar&id=X
//   eliminar  (POST)  ?accion=eliminar&id=X
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/programascontroller.php';

$accion = $_GET['accion'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $controller = new ProgramasController();

    if ($accion !== '') {
        switch ($accion) {
            case 'actualizar':
                $controller->actualizar($id);
                break;
            case 'eliminar':
                $controller->eliminar($id);
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
