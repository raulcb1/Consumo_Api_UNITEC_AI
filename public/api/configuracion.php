<?php
// public/api/configuracion.php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/configuracioncontroller.php';

$accion = $_GET['accion'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1; // Por defecto equipo 1

try {
    $controller = new ConfiguracionController();

    switch ($accion) {
        case 'listar':
            $controller->listar();
            break;
        case 'crear':
            $controller->crear();
            break;
        case 'actualizar':
            $controller->actualizar($id);
            break;
        case 'eliminar':
            $controller->eliminar($id);
            break;
        case 'seleccionar':
            $controller->seleccionar($id);
            break;
        default:
            $controller->obtener($id);
    }
} catch (Exception $e) {
    Response::error('Error interno: ' . $e->getMessage(), 500);
}
