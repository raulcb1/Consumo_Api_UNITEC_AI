<?php
// public/api/exportar_excel.php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/response.php';
require_once __DIR__ . '/../../app/controllers/exportarcontroller.php';

$loteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($loteId <= 0) {
    die("ID de lote inválido.");
}

try {
    $controller = new ExportarController();
    $controller->reporteCalidad($loteId);
} catch (Exception $e) {
    die("Error al generar Excel: " . $e->getMessage());
}
