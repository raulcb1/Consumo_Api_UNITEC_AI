<?php
// public/api/visordatos.php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/visordatoscontroller.php';

$accion = $_GET['accion'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $controller = new VisorDatosController();

    if ($accion === 'listar_lotes') {
        $controller->listarLotes();
    } elseif ($accion === 'detalle' && $id > 0) {
        $controller->obtenerDetalleLote($id);
    } else {
        Response::error('Acción no válida', 400);
    }
} catch (Exception $e) {
    Response::error('Error interno: ' . $e->getMessage(), 500);
}