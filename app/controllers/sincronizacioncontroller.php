<?php
// app/controllers/sincronizacioncontroller.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/unitec_api_client.php';
require_once __DIR__ . '/../models/linea.php';
require_once __DIR__ . '/../models/lote.php';
require_once __DIR__ . '/../models/conteo_lote.php';
require_once __DIR__ . '/../models/detalle_clase.php';
require_once __DIR__ . '/../models/distribucion_lote.php';

class SincronizacionController
{
    private int $equipoId = 1;
    private UnitecApiClient $cliente;
    private Linea $lineaModelo;
    private Lote $loteModelo;
    private ConteoLote $conteoModelo;
    private DetalleClase $detalleModelo;

    public function __construct()
    {
        Auth::requiereSesion();
        $this->equipoId = isset($_GET['equipo_id']) ? (int)$_GET['equipo_id'] : ($_SESSION['equipo_id'] ?? 1);

        try {
            $this->cliente = new UnitecApiClient($this->equipoId);
        } catch (Exception $e) {
            Response::error('No se pudo inicializar el cliente: ' . $e->getMessage(), 500);
        }
        $this->lineaModelo = new Linea();
        $this->loteModelo = new Lote();
        $this->conteoModelo = new ConteoLote();
        $this->detalleModelo = new DetalleClase();
    }

    public function sincronizarTodo(): void
    {
        if (!Auth::tienePermiso('sincronizacion', 'ejecutar')) Response::error('Sin permiso', 403);

        // Forzar recarga si se envía ?forzar=true en la URL
        $forzar = isset($_GET['forzar']) && $_GET['forzar'] === 'true';

        try {
            $lineas = $this->procesarSincronizacionLineas();
            $resultadoLotes = $this->procesarSincronizacionLotes($forzar);

            Response::exito([
                'lineas' => $lineas,
                'lotes_nuevos' => $resultadoLotes['nuevos'],
                'lotes_actualizados' => $resultadoLotes['actualizados'],
                'lotes_saltados' => $resultadoLotes['saltados']
            ], "Sincronización optimizada: {$resultadoLotes['nuevos']} nuevos, {$resultadoLotes['actualizados']} actualizados, {$resultadoLotes['saltados']} sin cambios.");
        } catch (Exception $e) {
            Response::error('Error en sincronización: ' . $e->getMessage(), 502);
        }
    }

    private function procesarSincronizacionLineas(): int
    {
        $lineasApi = $this->cliente->obtener_lineas();
        $usuario = Auth::usuarioActual();
        $count = 0;
        foreach ($lineasApi as $linea) {
            $codigoLinea = (string)$linea['line'];
            $existente = $this->lineaModelo->obtenerPorCodigo($codigoLinea, $this->equipoId);
            $datos = [
                'linea' => $codigoLinea,
                'descripcion' => $linea['description'] ?? 'Sin descripción',
                'equipo_id' => $this->equipoId,
                'updated_by' => $usuario['usuario_id'] ?? 1
            ];
            if ($existente) {
                $this->lineaModelo->actualizar((int)$existente['id'], $datos);
            } else {
                $datos['created_by'] = $usuario['usuario_id'] ?? 1;
                $this->lineaModelo->crear($datos);
            }
            $count++;
        }
        return $count;
    }

    private function procesarSincronizacionLotes(bool $forzar = false): array
    {
        $lineas = $this->lineaModelo->listarActivas($this->equipoId);
        $usuario = Auth::usuarioActual();
        $usuarioId = $usuario['usuario_id'] ?? 1;
        $db = Database::getConexion();

        $nuevos = 0;
        $actualizados = 0;
        $saltados = 0;

        foreach ($lineas as $linea) {
            // 1. Obtener todos los lotes de la línea desde la API
            $lotesApi = $this->cliente->obtener_lotes($linea['linea']);

            foreach ($lotesApi as $loteApi) {
                $lotIdApi = (int)$loteApi['lotId'];
                $fechaFinApi = !empty($loteApi['endDate']) ? str_replace('T', ' ', $loteApi['endDate']) : null;

                // 2. Verificar si el lote ya existe en la BD local
                $existente = $this->loteModelo->obtenerPorLotIdApi($lotIdApi, (int)$linea['id'], $this->equipoId);

                $datosLote = [
                    'lot_id_api' => $lotIdApi,
                    'nombre_lote' => $loteApi['lotName'],
                    'linea_id' => (int)$linea['id'],
                    'programa' => $loteApi['productionProgramName'] ?? null,
                    'equipo_id' => $this->equipoId,
                    'fecha_inicio' => !empty($loteApi['startDate']) ? str_replace('T', ' ', $loteApi['startDate']) : null,
                    'fecha_fin' => $fechaFinApi,
                    'codigo_productor' => $loteApi['grower'] ?? null,
                    'descripcion_variedad' => $loteApi['variety'] ?? null,
                    'paquetes' => $loteApi['boxNumber'] ?? 0,
                    'updated_by' => $usuarioId
                ];

                if ($existente) {
                    $loteIdLocal = (int)$existente['id'];

                    // Verificar si ya tiene estadísticas en la BD local
                    $stmt = $db->prepare("SELECT COUNT(*) FROM conteos_lote WHERE lote_id = :lid");
                    $stmt->execute(['lid' => $loteIdLocal]);
                    $tieneStats = (int)$stmt->fetchColumn() > 0;

                    // 3. Lógica de OPTIMIZACIÓN
                    // Si el lote ya terminó (tiene fecha fin), ya tiene stats, y NO estamos forzando:
                    // Solo actualizamos el lote (por si cambió la variedad, cajas, etc.) pero NO llamamos a la API de stats.
                    if ($fechaFinApi !== null && $tieneStats && !$forzar) {
                        $this->loteModelo->actualizar($loteIdLocal, $datosLote);
                        $saltados++;
                        continue; // Saltamos a la siguiente iteración
                    }

                    $this->loteModelo->actualizar($loteIdLocal, $datosLote);
                    $actualizados++;
                } else {
                    $datosLote['created_by'] = $usuarioId;
                    $loteIdLocal = $this->loteModelo->crear($datosLote);
                    $nuevos++;
                }

                // 4. Descargar Estadísticas SOLO si es nuevo, está activo, o se forzó la recarga
                $this->procesarEstadisticasLote($linea['linea'], $lotIdApi, $loteIdLocal);
            }
        }

        return ['nuevos' => $nuevos, 'actualizados' => $actualizados, 'saltados' => $saltados];
    }

    private function procesarEstadisticasLote(string $lineCode, int $lotId, int $loteIdLocal): void
    {
        // 1. Obtener la data de la API
        $resClases = $this->cliente->obtener_clases($lineCode, $lotId);
        $resConteos = $this->cliente->obtener_conteos($lineCode, $lotId);

        if (empty($resClases['classCounts']) && empty($resConteos['measureCounts'])) return;

        $fechaAnalisis = date('Y-m-d H:i:s');
        if (!empty($resClases['endDate'])) {
            $fechaAnalisis = date('Y-m-d H:i:s', strtotime($resClases['endDate']));
        }

        // 2. Iniciar Transacción (Optimización de escritura en BD)
        $db = Database::getConexion();
        $db->beginTransaction();

        try {
            // Guardar Cabecera
            $conteoId = $this->conteoModelo->crear([
                'lote_id' => $loteIdLocal,
                'codigo_externo_lote' => $resClases['lotName'] ?? null,
                'consultado_en' => $fechaAnalisis,
                'brix_promedio' => $resConteos['brixAverage'] ?? null,
                'brix_bajo_umbral' => $resConteos['fruitsOverBrixThresholdPercentage'] ?? null,
                'acidez_promedio' => $resConteos['acidityAverage'] ?? null,
                'acidez_alta_umbral' => $resConteos['fruitsOverAcidityThresholdPercentage'] ?? null
            ]);

            // Guardar Clases
            if (!empty($resClases['classCounts'])) {
                foreach ($resClases['classCounts'] as $clase) {
                    $this->detalleModelo->crear([
                        'conteo_lote_id' => $conteoId,
                        'tipo_programa' => 'production',
                        'nombre_clase' => $clase['className'],
                        'porcentaje_clase' => $clase['classPercentage']
                    ]);
                }
            }

            // Guardar Medidas y Defectos
            if (!empty($resConteos['measureCounts'])) {
                $distModelo = new DistribucionLote();
                foreach ($resConteos['measureCounts'] as $medida) {
                    $criteria = $medida['criteriaCode'];
                    if (!empty($medida['rangeCounts'])) {
                        foreach ($medida['rangeCounts'] as $rango) {
                            $rangeName = trim(strtoupper($rango['rangeName']));

                            if ($criteria === 'AvgDiameter' || $criteria === 'Weight' || $criteria === 'PercColor') {
                                $distModelo->crear([
                                    'conteo_lote_id' => $conteoId,
                                    'criterio' => $criteria,
                                    'rango' => $rango['rangeName'],
                                    'porcentaje' => $rango['rangePercentage']
                                ]);
                            }

                            if (($criteria === 'PercColor' && $rangeName === 'CAT2') || $criteria === 'Defects') {
                                if (!empty($rango['ruleCounts'])) {
                                    foreach ($rango['ruleCounts'] as $regla) {
                                        $nombreDefecto = str_replace('Defect_', '', $regla['ruleDesc']);
                                        $nombreDefecto = str_replace('_', ' ', $nombreDefecto);
                                        $distModelo->crear([
                                            'conteo_lote_id' => $conteoId,
                                            'criterio' => 'Defects',
                                            'rango' => $nombreDefecto,
                                            'porcentaje' => $regla['rulePercentage']
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception("Error al guardar estadísticas del lote $lotId: " . $e->getMessage());
        }
    }

    // =================================================================
    // LOGS DE SINCRONIZACIÓN
    // =================================================================
    public function listarLogs(): void
    {
        if (!Auth::tienePermiso('sincronizacion', 'ver')) {
            Response::error('Sin permiso', 403);
        }
        // Asegúrate de tener el require_once de RegistroSincronizacion arriba,
        // si no, agrégalo al inicio del archivo.
        $syncLog = new RegistroSincronizacion();
        Response::exito($syncLog->listarRecientes(100), 'Últimos registros');
    }

    // =================================================================
    // LISTAR LÍNEAS LOCALES (Para el select del frontend)
    // =================================================================
    public function listarLineasLocales(): void
    {
        if (!Auth::tienePermiso('sincronizacion', 'ver')) {
            Response::error('Sin permiso', 403);
        }
        $lineas = $this->lineaModelo->listarActivas($this->equipoId);
        Response::exito($lineas, 'Líneas locales cargadas');
    }
}
