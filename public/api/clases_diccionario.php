<?php
// =====================================================================
// clases_diccionario.php - Endpoint AJAX del diccionario de clases
// =====================================================================
// Ubicación: public/api/clases_diccionario.php
//
// Acciones:
//   listar            (GET)   ?incluir_inactivas=true
//   listar_faltantes  (GET)
//   obtener           (GET)   ?id=X
//   crear             (POST)
//   crear_desde_deteccion (POST)
//   actualizar        (POST)  ?accion=actualizar&id=X
//   eliminar          (POST)  ?accion=eliminar&id=X
//   restaurar         (POST)  ?accion=restaurar&id=X
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/clases_diccionario_controller.php';

$accion = $_GET['accion'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $controller = new ClasesDiccionarioController();

    if ($accion !== '') {
        switch ($accion) {
            case 'actualizar':
                $controller->actualizar($id);
                break;
            case 'eliminar':
                $controller->eliminar($id);
                break;
            case 'restaurar':
                $controller->restaurar($id);
                break;
            case 'listar_faltantes':
                $controller->listarFaltantes();
                break;
            case 'crear_desde_deteccion':
                $controller->crearDesdeDeteccion();
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
