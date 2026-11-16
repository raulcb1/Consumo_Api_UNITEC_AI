<?php
// app/controllers/analiticacontroller.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../config/database.php';

class AnaliticaController
{
    public function __construct()
    {
        Auth::requiereSesion();
    }

    public function datosDashboard(): void
    {
        if (!Auth::tienePermiso('panel', 'ver')) Response::error('No tienes permiso.', 403);

        $db = Database::getConexion();
        $equipoId = $_SESSION['equipo_id'] ?? 1;

        // 1. DATOS DEL LOTE (Información general)
        $sqlLotes = "SELECT 
                        id AS lote_id, 
                        lot_id_api, 
                        nombre_lote, 
                        programa, 
                        descripcion_variedad, 
                        fecha_inicio, 
                        paquetes 
                    FROM lotes 
                    WHERE deleted_at IS NULL AND equipo_id = :equipo_id";
        $stmt = $db->prepare($sqlLotes);
        $stmt->execute(['equipo_id' => $equipoId]);
        $datos_lote = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. CALIDAD INTERNA (Brix y Acidez)
        $sqlInterna = "SELECT 
                        cl.lote_id, 
                        cl.brix_promedio, 
                        cl.brix_bajo_umbral, 
                        cl.acidez_promedio, 
                        cl.acidez_alta_umbral
                    FROM conteos_lote cl
                    INNER JOIN lotes l ON l.id = cl.lote_id
                    INNER JOIN (SELECT MAX(id) as id FROM conteos_lote GROUP BY lote_id) ult ON ult.id = cl.id
                    WHERE l.deleted_at IS NULL AND l.equipo_id = :equipo_id";
        $stmt = $db->prepare($sqlInterna);
        $stmt->execute(['equipo_id' => $equipoId]);
        $interna = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. CLASES FINALES (BUENO, CAT2, etc.)
        $sqlClases = "SELECT 
                        cl.lote_id,
                        dc.nombre_clase, 
                        dc.porcentaje_clase
                    FROM detalles_clase dc
                    INNER JOIN conteos_lote cl ON cl.id = dc.conteo_lote_id
                    INNER JOIN lotes l ON l.id = cl.lote_id
                    INNER JOIN (SELECT MAX(id) as id FROM conteos_lote GROUP BY lote_id) ult ON ult.id = cl.id
                    WHERE l.deleted_at IS NULL AND l.equipo_id = :equipo_id";
        $stmt = $db->prepare($sqlClases);
        $stmt->execute(['equipo_id' => $equipoId]);
        $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. MEDIDAS Y DEFECTOS (Calibres, Colores, Defectos)
        $sqlMedidas = "SELECT 
                        cl.lote_id,
                        d.criterio, 
                        d.rango, 
                        d.porcentaje
                    FROM distribuciones_lote d
                    INNER JOIN conteos_lote cl ON cl.id = d.conteo_lote_id
                    INNER JOIN lotes l ON l.id = cl.lote_id
                    INNER JOIN (SELECT MAX(id) as id FROM conteos_lote GROUP BY lote_id) ult ON ult.id = cl.id
                    WHERE l.deleted_at IS NULL AND l.equipo_id = :equipo_id";
        $stmt = $db->prepare($sqlMedidas);
        $stmt->execute(['equipo_id' => $equipoId]);
        $medidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Respuesta Estructurada
        Response::exito([
            'datos_lote' => $datos_lote,
            'interna'    => $interna,
            'clases'     => $clases,
            'medidas'    => $medidas
        ], 'Datos del dashboard cargados');
    }
}
