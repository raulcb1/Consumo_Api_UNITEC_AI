<?php
// =====================================================================
// lotes.php - Endpoint AJAX para el módulo de lotes
// =====================================================================
// Ubicación: public/api/lotes.php
//
// Acciones:
//   listar        (GET)   ?linea_id=&buscar=&codigo_externo=&fecha_inicio=&fecha_fin=&solo_actual=
//   obtener       (GET)   ?id=X
//   crear         (POST)  body JSON
//   actualizar    (POST)  ?accion=actualizar&id=X
//   eliminar      (POST)  ?accion=eliminar&id=X
//   iniciar       (POST)  ?accion=iniciar&id=X
//   detener       (POST)  ?accion=detener&id=X
//   estadisticas  (GET)   ?accion=estadisticas&id=X    (trae de UNITEC y guarda)
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/lotescontroller.php';

$accion = $_GET['accion'] ?? '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $controller = new LotesController();

    // Rutas con acción explícita
    if ($accion !== '') {
        switch ($accion) {
            case 'actualizar':
                $controller->actualizar($id);
                break;
            case 'eliminar':
                $controller->eliminar($id);
                break;
            case 'iniciar':
                $controller->iniciar($id);
                break;
            case 'detener':
                $controller->detener($id);
                break;
            case 'estadisticas':
                $controller->estadisticas($id);
                break;
            case 'estadisticas_guardadas':
                $controller->estadisticasGuardadas($id);
                break;
            default:
                Response::error('Acción no válida.', 400);
        }
        exit;
    }

    // Rutas por método HTTP
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
