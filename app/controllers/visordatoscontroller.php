<?php
// app/controllers/visordatoscontroller.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../config/database.php';

class VisorDatosController
{
    public function __construct()
    {
        Auth::requiereSesion();
    }

    public function listarLotes(): void
    {
        $db = Database::getConexion();
        $equipoId = $_SESSION['equipo_id'] ?? 1;
        
        $stmt = $db->prepare("SELECT id, lot_id_api, nombre_lote, fecha_inicio FROM lotes WHERE equipo_id = :eq AND deleted_at IS NULL ORDER BY id DESC LIMIT 100");
        $stmt->execute(['eq' => $equipoId]);
        $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        Response::exito($lotes, 'Lotes cargados');
    }

    public function obtenerDetalleLote(int $id): void
    {
        $db = Database::getConexion();
        
        // 1. Datos del lote
        $stmt = $db->prepare("SELECT * FROM lotes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$lote) Response::error('Lote no encontrado', 404);

        // 2. Último snapshot (conteo)
        $stmt = $db->prepare("SELECT * FROM conteos_lote WHERE lote_id = :id ORDER BY id DESC LIMIT 1");
        $stmt->execute(['id' => $id]);
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);

        $clases = [];
        $distribuciones = [];

        if ($conteo) {
            // 3. Clases (BUENO, CAT2, etc.)
            $stmt = $db->prepare("SELECT * FROM detalles_clase WHERE conteo_lote_id = :cid");
            $stmt->execute(['cid' => $conteo['id']]);
            $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Distribuciones (Calibres, Colores, Defectos)
            $stmt = $db->prepare("SELECT * FROM distribuciones_lote WHERE conteo_lote_id = :cid ORDER BY criterio ASC, porcentaje DESC");
            $stmt->execute(['cid' => $conteo['id']]);
            $distribuciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        Response::exito([
            'lote' => $lote,
            'conteo' => $conteo,
            'clases' => $clases,
            'distribuciones' => $distribuciones
        ], 'Detalle cargado');
    }
}