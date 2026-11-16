<?php
// =====================================================================
// sincronizacion.php - Endpoint AJAX para sincronización con UNITEC
// =====================================================================
// Ubicación: public/api/sincronizacion.php
//
// Acciones (query string ?accion=...):
//   lineas        (POST) - Sincroniza todas las líneas
//   programas     (POST) - Sincroniza programas (body: linea_id)
//   lotes         (POST) - Sincroniza lotes (body: linea_id, solo_actual)
//   todo          (POST) - Sincroniza líneas + programas + lotes
//                         (body opcional: linea_id)
//   logs          (GET)  - Últimos registros de sincronización
//   listar_lineas (GET)  - Líneas locales (para el selector)
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/sincronizacioncontroller.php';

$accion = $_GET['accion'] ?? '';

try {
    $controller = new SincronizacionController();

    switch ($accion) {
        case 'lineas':
            $controller->sincronizarLineas();
            break;

        case 'programas':
            $controller->sincronizarProgramas();
            break;

        case 'lotes':
            $controller->sincronizarLotes();
            break;

        case 'todo':
            $controller->sincronizarTodo();
            break;

        case 'logs':
            $controller->listarLogs();
            break;

        case 'listar_lineas':
            $controller->listarLineasLocales();
            break;

        case 'estadisticas':
            $controller->sincronizarEstadisticas();
            break;

        default:
            Response::error('Acción no válida.', 400);
    }
} catch (Exception $e) {
    Response::error('Error interno: ' . $e->getMessage(), 500);
}
