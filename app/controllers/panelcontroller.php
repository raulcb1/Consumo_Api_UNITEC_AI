<?php
// =====================================================================
// panelcontroller.php - Controlador del panel principal (dashboard)
// =====================================================================
// Ubicación: app/controllers/panelcontroller.php
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../models/linea.php';
require_once __DIR__ . '/../models/programa.php';
require_once __DIR__ . '/../models/lote.php';
//require_once __DIR__ . '/../models/conteo_lote.php';

class PanelController
{
    /**
     * Devuelve un resumen general del sistema en formato JSON.
     * Usado por el dashboard para mostrar tarjetas y estadísticas rápidas.
     */
    public function resumen(): void
    {
        Auth::requiereSesion();

        if (!Auth::tienePermiso('panel', 'ver')) {
            Response::error('No tienes permiso para acceder al panel.', 403);
        }

        $lineaModel    = new Linea();
        $programaModel = new Programa();
        $loteModel     = new Lote();

        // Líneas activas
        $lineas = $lineaModel->listarActivas();

        // Programas totales
        $programas = $programaModel->listar();

        // Lotes totales
        $lotes = $loteModel->listar();

        // Lotes activos (con procesamiento actual)
        $lotesActivos = $this->contarLotesActivos();

        // Últimos 5 lotes creados
        $ultimosLotes = array_slice($lotes, 0, 5);

        $resumen = [
            'total_lineas'    => count($lineas),
            'total_programas' => count($programas),
            'total_lotes'     => count($lotes),
            'lotes_activos'   => $lotesActivos,
            'lineas'          => $lineas,
            'ultimos_lotes'   => $ultimosLotes
        ];

        Response::exito($resumen, 'Resumen del sistema');
    }

    /**
     * Cuenta los lotes que tienen un procesamiento en curso (es_actual = 1).
     */
    private function contarLotesActivos(): int
    {
        $db = Database::getConexion();
        $stmt = $db->query(
            "SELECT COUNT(DISTINCT lote_id) AS total
             FROM procesamientos_lotes
             WHERE es_actual = 1"
        );
        $fila = $stmt->fetch();
        return (int) ($fila['total'] ?? 0);
    }
}