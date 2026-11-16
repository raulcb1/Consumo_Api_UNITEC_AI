<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/analiticacontroller.php';

try {
    $controller = new AnaliticaController();
    if (($_GET['accion'] ?? '') === 'datos_dashboard') {
        $controller->datosDashboard();
    } else {
        Response::error('Acción no válida', 400);
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
