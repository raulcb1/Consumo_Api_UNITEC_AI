<?php
// =====================================================================
// lotescontroller.php - Controlador de lotes
// =====================================================================
// Ubicación: app/controllers/lotescontroller.php
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/unitec_api_client.php';
require_once __DIR__ . '/../models/lote.php';
require_once __DIR__ . '/../models/linea.php';
require_once __DIR__ . '/../models/programa.php';
require_once __DIR__ . '/../models/procesamiento_lote.php';
require_once __DIR__ . '/../models/conteo_lote.php';
require_once __DIR__ . '/../models/detalle_clase.php';
require_once __DIR__ . '/../models/conteo_desecho.php';
require_once __DIR__ . '/../models/promedio_lote.php';
require_once __DIR__ . '/../models/promedio_clase.php';
require_once __DIR__ . '/../models/registros_auditoria.php';

class LotesController
{
    private Lote $modelo;
    private Linea $lineaModelo;
    private Programa $programaModelo;
    private ProcesamientoLote $procesamientoModelo;
    private ConteoLote $conteoModelo;
    private DetalleClase $detalleModelo;
    private ConteoDesecho $desechoModelo;
    private PromedioLote $promedioLoteModelo;
    private PromedioClase $promedioClaseModelo;
    private RegistroAuditoria $auditoria;

    public function __construct()
    {
        Auth::requiereSesion();
        $this->modelo              = new Lote();
        $this->lineaModelo         = new Linea();
        $this->programaModelo      = new Programa();
        $this->procesamientoModelo = new ProcesamientoLote();
        $this->conteoModelo        = new ConteoLote();
        $this->detalleModelo       = new DetalleClase();
        $this->desechoModelo       = new ConteoDesecho();
        $this->promedioLoteModelo  = new PromedioLote();
        $this->promedioClaseModelo = new PromedioClase();
        $this->auditoria           = new RegistroAuditoria();
    }

    // -----------------------------------------------------------------
    // LISTAR - GET
    // Filtros opcionales (query string): linea_id, codigo_externo,
    //                                    fecha_inicio, fecha_fin, solo_actual
    // -----------------------------------------------------------------
    public function listar(): void
    {
        if (!Auth::tienePermiso('lotes', 'ver')) {
            Response::error('No tienes permiso para ver los lotes.', 403);
        }

        $lineaId    = isset($_GET['linea_id']) && $_GET['linea_id'] !== '' ? (int) $_GET['linea_id'] : 0;
        $soloActual = isset($_GET['solo_actual']) && $_GET['solo_actual'] === 'true';

        $filtros = [];
        if (!empty($_GET['codigo_externo'])) $filtros['codigo_externo'] = $_GET['codigo_externo'];
        if (!empty($_GET['fecha_inicio']))   $filtros['fecha_inicio']   = $_GET['fecha_inicio'];
        if (!empty($_GET['fecha_fin']))      $filtros['fecha_fin']      = $_GET['fecha_fin'];
        if (!empty($_GET['buscar']))         $filtros['buscar']         = $_GET['buscar'];
        if (!empty($_GET['solo_incompletos']) && $_GET['solo_incompletos'] === 'true') {
            $filtros['solo_incompletos'] = true;
        }

        $lotes = $this->modelo->listarGeneral($lineaId, $filtros, $soloActual);

        // Enriquecer con datos de línea para la tabla
        $lineas = [];
        foreach ($this->lineaModelo->listarActivas() as $l) {
            $lineas[(int) $l['id']] = $l;
        }
        foreach ($lotes as &$lote) {
            $lote['linea_codigo']      = $lineas[(int) $lote['linea_id']]['linea']       ?? '—';
            $lote['linea_descripcion'] = $lineas[(int) $lote['linea_id']]['descripcion'] ?? '';
        }
        unset($lote);

        Response::exito($lotes, 'Listado de lotes');
    }

    // -----------------------------------------------------------------
    // OBTENER POR ID - GET
    // -----------------------------------------------------------------
    public function obtener(int $id): void
    {
        if (!Auth::tienePermiso('lotes', 'ver')) {
            Response::error('No tienes permiso para ver el lote.', 403);
        }

        $lote = $this->modelo->obtenerPorId($id);
        if (!$lote) {
            Response::error('Lote no encontrado.', 404);
        }

        // Adjuntar procesamientos
        $lote['procesamientos'] = $this->procesamientoModelo->listarPorLote($id);

        // Obtener el último snapshot (conteo) para mostrar Brix, Acidez, etc.
        $conteo = $this->conteoModelo->obtenerUltimoPorLote($id);

        $lote['conteo'] = $conteo;
        $lote['clases'] = [];
        $lote['distribuciones'] = [];

        if ($conteo) {
            // Clases finales (BUENO, CAT2, etc.)
            $lote['clases'] = $this->detalleModelo->listarPorConteo((int)$conteo['id']);

            // Distribuciones (Calibres, Defectos, etc.)
            $db = Database::getConexion();
            $stmt = $db->prepare("SELECT * FROM distribuciones_lote WHERE conteo_lote_id = :cid ORDER BY porcentaje DESC");
            $stmt->execute(['cid' => $conteo['id']]);
            $lote['distribuciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        Response::exito($lote, 'Detalle del lote');
    }
    
    // -----------------------------------------------------------------
    // CREAR - POST
    // Crea el lote en la API UNITEC y lo guarda localmente
    // -----------------------------------------------------------------
    public function crear(): void
    {
        if (!Auth::tienePermiso('lotes', 'crear')) {
            Response::error('No tienes permiso para crear lotes.', 403);
        }

        $datos = $this->leerJson();
        $this->validar($datos, ['linea_id', 'nombre_lote', 'programa_produccion_id']);

        // Validar línea
        $linea = $this->lineaModelo->obtenerPorId((int) $datos['linea_id']);
        if (!$linea) {
            Response::error('La línea indicada no existe.', 404);
        }

        // Validar programa
        $programa = $this->programaModelo->obtenerPorIdApi(
            (int) $datos['programa_produccion_id'],
            (int) $datos['linea_id']
        );
        if (!$programa) {
            Response::error('El programa indicado no existe para esta línea.', 404);
        }

        // Evitar duplicados locales
        if ($this->modelo->obtenerPorNombreYLinea($datos['nombre_lote'], (int) $datos['linea_id'])) {
            Response::error('Ya existe un lote con ese nombre en esta línea.', 409);
        }

        // ------- Llamada a la API UNITEC -------
        try {
            $cliente = new UnitecApiClient();
            $payload = [
                'name'               => $datos['nombre_lote'],
                'externalCode'       => $datos['codigo_externo'] ?? null,
                'growerCode'         => $datos['codigo_productor'] ?? null,
                'growerDescription'  => $datos['descripcion_productor'] ?? null,
                'commodityCode'      => $datos['codigo_mercancia'] ?? null,
                'commodityDescription' => $datos['descripcion_mercancia'] ?? null,
                'varietyGroupCode'   => $datos['codigo_grupo_variedad'] ?? null,
                'varietyGroupDescription' => $datos['descripcion_grupo_variedad'] ?? null,
                'varietyCode'        => $datos['codigo_variedad'] ?? null,
                'varietyDescription' => $datos['descripcion_variedad'] ?? null,
                'packages'           => $datos['paquetes'] ?? null,
                'weight'             => $datos['peso'] ?? null,
                'notes'              => $datos['notas'] ?? null,
                'productionProgramId' => (int) $datos['programa_produccion_id'],
                'accountingProgramId' => isset($datos['programa_contable_id']) ? (int) $datos['programa_contable_id'] : null
            ];
            $respuestaApi = $cliente->crear_lote($linea['linea'], $payload);
        } catch (Exception $e) {
            Response::error('Error al crear el lote en la API UNITEC: ' . $e->getMessage(), 502);
        }

        // ------- Guardar localmente -------
        $usuario = Auth::usuarioActual();
        $datosLocales = [
            'nombre_lote'                => $datos['nombre_lote'],
            'codigo_externo'             => $datos['codigo_externo'] ?? null,
            'linea_id'                   => (int) $datos['linea_id'],
            'codigo_productor'           => $datos['codigo_productor'] ?? null,
            'descripcion_productor'      => $datos['descripcion_productor'] ?? null,
            'codigo_mercancia'           => $datos['codigo_mercancia'] ?? null,
            'descripcion_mercancia'      => $datos['descripcion_mercancia'] ?? null,
            'codigo_grupo_variedad'      => $datos['codigo_grupo_variedad'] ?? null,
            'descripcion_grupo_variedad' => $datos['descripcion_grupo_variedad'] ?? null,
            'codigo_variedad'            => $datos['codigo_variedad'] ?? null,
            'descripcion_variedad'       => $datos['descripcion_variedad'] ?? null,
            'paquetes'                   => $datos['paquetes'] ?? null,
            'peso'                       => $datos['peso'] ?? null,
            'notas'                      => $datos['notas'] ?? null,
            'created_by'                 => $usuario['usuario_id'],
            'updated_by'                 => $usuario['usuario_id']
        ];

        $id = $this->modelo->crear($datosLocales);
        if (!$id) {
            Response::error('El lote se creó en la API pero no se pudo guardar localmente.', 500);
        }

        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lotes',
            $id,
            'INSERT',
            null,
            $this->modelo->obtenerPorId($id)
        );

        Response::exito($this->modelo->obtenerPorId($id), 'Lote creado correctamente');
    }

    // -----------------------------------------------------------------
    // ACTUALIZAR - POST
    // -----------------------------------------------------------------
    public function actualizar(int $id): void
    {
        if (!Auth::tienePermiso('lotes', 'editar')) {
            Response::error('No tienes permiso para editar lotes.', 403);
        }

        $lote = $this->modelo->obtenerPorId($id);
        if (!$lote) {
            Response::error('Lote no encontrado.', 404);
        }

        $datos = $this->leerJson();

        // Campos editables (no permitimos cambiar nombre_lote ni linea_id)
        $permitidos = [
            'codigo_externo',
            'codigo_productor',
            'descripcion_productor',
            'codigo_mercancia',
            'descripcion_mercancia',
            'codigo_grupo_variedad',
            'descripcion_grupo_variedad',
            'codigo_variedad',
            'descripcion_variedad',
            'paquetes',
            'peso',
            'notas'
        ];
        $actualizables = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $actualizables[$campo] = $datos[$campo];
            }
        }

        if (empty($actualizables)) {
            Response::error('No hay campos válidos para actualizar.', 400);
        }

        $usuario = Auth::usuarioActual();
        $actualizables['updated_by'] = $usuario['usuario_id'];

        if (!$this->modelo->actualizar($id, $actualizables)) {
            Response::error('No se pudo actualizar el lote.', 500);
        }

        $loteActualizado = $this->modelo->obtenerPorId($id);

        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lotes',
            $id,
            'UPDATE',
            $lote,
            $loteActualizado
        );

        Response::exito($loteActualizado, 'Lote actualizado correctamente');
    }

    // -----------------------------------------------------------------
    // ELIMINAR (SOFT DELETE) - POST
    // -----------------------------------------------------------------
    public function eliminar(int $id): void
    {
        if (!Auth::tienePermiso('lotes', 'eliminar')) {
            Response::error('No tienes permiso para eliminar lotes.', 403);
        }

        $lote = $this->modelo->obtenerPorId($id);
        if (!$lote) {
            Response::error('Lote no encontrado.', 404);
        }

        if (!$this->modelo->eliminar($id)) {
            Response::error('No se pudo eliminar el lote.', 500);
        }

        $usuario = Auth::usuarioActual();

        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lotes',
            $id,
            'SOFT_DELETE',
            $lote,
            null
        );

        Response::exito(null, 'Lote eliminado correctamente');
    }

    // -----------------------------------------------------------------
    // INICIAR - POST (llama a la API /run)
    // -----------------------------------------------------------------
    public function iniciar(int $id): void
    {
        if (!Auth::tienePermiso('lotes', 'iniciar')) {
            Response::error('No tienes permiso para iniciar lotes.', 403);
        }

        $lote = $this->modelo->obtenerPorId($id);
        if (!$lote) {
            Response::error('Lote no encontrado.', 404);
        }
        $linea = $this->lineaModelo->obtenerPorId((int) $lote['linea_id']);
        if (!$linea) {
            Response::error('La línea asociada al lote no existe.', 404);
        }

        try {
            $cliente = new UnitecApiClient();
            $respuestaApi = $cliente->iniciar_lote($linea['linea'], $lote['nombre_lote']);
        } catch (Exception $e) {
            Response::error('Error al iniciar el lote en la API UNITEC: ' . $e->getMessage(), 502);
        }

        // Actualizar procesamientos: marcar todos como no actuales y crear uno nuevo
        $usuario = Auth::usuarioActual();
        $this->procesamientoModelo->marcarTodosNoActuales($id);

        $this->procesamientoModelo->crear([
            'lote_id'                    => $id,
            'fecha_inicio'               => date('Y-m-d H:i:s'),
            'nombre_programa_produccion' => $respuestaApi['productionProgramName'] ?? '',
            'programa_produccion_id'     => (int) ($respuestaApi['productionProgramId'] ?? 0),
            'nombre_programa_contable'   => $respuestaApi['accountingProgramName'] ?? null,
            'programa_contable_id'       => isset($respuestaApi['accountingProgramId']) ? (int) $respuestaApi['accountingProgramId'] : null,
            'es_actual'                  => 1,
            'notas'                      => $respuestaApi['notes'] ?? null,
            'created_by'                 => $usuario['usuario_id'],
            'updated_by'                 => $usuario['usuario_id']
        ]);

        // Actualizar fecha_inicio del lote si aún no la tiene
        if (empty($lote['fecha_inicio'])) {
            $this->modelo->actualizar($id, ['fecha_inicio' => date('Y-m-d H:i:s')]);
        }

        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lotes',
            $id,
            'UPDATE',
            $lote,
            $this->modelo->obtenerPorId($id)
        );

        Response::exito($this->modelo->obtenerPorId($id), 'Lote iniciado correctamente');
    }

    // -----------------------------------------------------------------
    // DETENER - POST (llama a la API /stop)
    // -----------------------------------------------------------------
    public function detener(int $id): void
    {
        if (!Auth::tienePermiso('lotes', 'detener')) {
            Response::error('No tienes permiso para detener lotes.', 403);
        }

        $lote = $this->modelo->obtenerPorId($id);
        if (!$lote) {
            Response::error('Lote no encontrado.', 404);
        }
        $linea = $this->lineaModelo->obtenerPorId((int) $lote['linea_id']);
        if (!$linea) {
            Response::error('La línea asociada al lote no existe.', 404);
        }

        try {
            $cliente = new UnitecApiClient();
            $cliente->detener_lote($linea['linea'], $lote['nombre_lote']);
        } catch (Exception $e) {
            Response::error('Error al detener el lote en la API UNITEC: ' . $e->getMessage(), 502);
        }

        // Marcar procesamiento actual como finalizado
        $procesamientoActual = $this->procesamientoModelo->obtenerActualPorLote($id);
        if ($procesamientoActual) {
            $this->procesamientoModelo->actualizar((int) $procesamientoActual['id'], [
                'fecha_fin'  => date('Y-m-d H:i:s'),
                'es_actual'  => 0
            ]);
        }

        // Actualizar fecha_fin del lote
        $this->modelo->actualizar($id, ['fecha_fin' => date('Y-m-d H:i:s')]);

        $usuario = Auth::usuarioActual();
        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lotes',
            $id,
            'UPDATE',
            $lote,
            $this->modelo->obtenerPorId($id)
        );

        Response::exito(null, 'Lote detenido correctamente');
    }

    // -----------------------------------------------------------------
    // ESTADÍSTICAS - GET (consulta classCounts y guarda histórico)
    // -----------------------------------------------------------------
    public function estadisticas(int $id): void
    {
        if (!Auth::tienePermiso('estadisticas', 'ver')) {
            Response::error('No tienes permiso para ver las estadísticas.', 403);
        }

        $lote = $this->modelo->obtenerPorId($id);
        if (!$lote) {
            Response::error('Lote no encontrado.', 404);
        }

        try {
            $resultado = $this->persistirEstadisticas($id);
        } catch (Exception $e) {
            Response::error('Error al obtener estadísticas de la API UNITEC: ' . $e->getMessage(), 502);
        }

        Response::exito([
            'conteo_id'     => $resultado['conteo_id'],
            'datos_api'     => $resultado['datos_api'],
            'consultado_en' => date('Y-m-d H:i:s')
        ], 'Estadísticas obtenidas y guardadas correctamente');
    }

    // -----------------------------------------------------------------
    // ESTADÍSTICAS GUARDADAS - GET
    // Devuelve el último snapshot guardado, en formato compatible con
    // el que entrega la API UNITEC (para reutilizar el mismo render).
    // -----------------------------------------------------------------
    public function estadisticasGuardadas(int $id): void
    {
        if (!Auth::tienePermiso('estadisticas', 'ver')) {
            Response::error('No tienes permiso para ver las estadísticas.', 403);
        }

        $lote = $this->modelo->obtenerPorId($id);
        if (!$lote) {
            Response::error('Lote no encontrado.', 404);
        }

        $conteo = $this->conteoModelo->obtenerUltimoPorLote($id);
        if (!$conteo) {
            Response::exito([
                'origen'      => 'bd',
                'tiene_datos' => false
            ], 'El lote aún no tiene estadísticas guardadas.');
        }

        // -------- Clases (producción y contabilidad) --------
        $detalles = $this->detalleModelo->listarPorConteo((int) $conteo['id']);
        $produccion = [];
        $contabilidad = [];

        foreach ($detalles as $d) {
            $proms = $this->promedioClaseModelo->listarPorDetalle((int) $d['id']);

            $item = [
                'classExternalCode' => $d['codigo_externo_clase'],
                'className'         => $d['nombre_clase'],
                'classGroupName'    => $d['nombre_grupo_clase'],
                'totalFruits'       => (int) $d['total_frutas'],
                'totalWeightKg'     => (float) $d['peso_total_kg'],
                'averageValues'     => array_map(function ($p) {
                    return [
                        'criteria' => $p['criterio'],
                        'rule'     => $p['regla'],
                        'avgScore' => (float) $p['puntaje_promedio']
                    ];
                }, $proms)
            ];

            if ($d['tipo_programa'] === 'production') {
                $produccion[] = $item;
            } else {
                $contabilidad[] = $item;
            }
        }

        // -------- Desechos --------
        $desechos = $this->desechoModelo->listarPorConteo((int) $conteo['id']);
        $wasteCounts = array_map(function ($w) {
            return [
                'wasteDescription' => $w['descripcion_desecho'],
                'wasteType'        => $w['tipo_desecho'],
                'totalWeightKg'    => (float) $w['peso_total_kg']
            ];
        }, $desechos);

        // -------- Promedios globales del lote --------
        $promsLote = $this->promedioLoteModelo->listarPorConteo((int) $conteo['id']);
        $averageValues = array_map(function ($p) {
            return [
                'criteria' => $p['criterio'],
                'rule'     => $p['regla'],
                'avgScore' => (float) $p['puntaje_promedio']
            ];
        }, $promsLote);

        Response::exito([
            'origen'                => 'bd',
            'tiene_datos'           => true,
            'consultado_en'         => $conteo['consultado_en'],
            'conteo_id'             => (int) $conteo['id'],
            'lotExternalCode'       => $conteo['codigo_externo_lote'],
            'productionClassCounts' => $produccion,
            'accountingClassCounts' => $contabilidad,
            'wasteCounts'           => $wasteCounts,
            'averageValues'         => $averageValues
        ], 'Estadísticas guardadas.');
    }

    // -----------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------
    private function guardarDetallesClase(int $conteoId, string $tipoPrograma, array $clases): void
    {
        foreach ($clases as $clase) {
            $detalleId = $this->detalleModelo->crear([
                'conteo_lote_id'       => $conteoId,
                'tipo_programa'        => $tipoPrograma,
                'codigo_externo_clase' => $clase['classExternalCode'] ?? null,
                'nombre_clase'         => $clase['className'] ?? '',
                'nombre_grupo_clase'   => $clase['classGroupName'] ?? null,
                'total_frutas'         => $clase['totalFruits'] ?? 0,
                'peso_total_kg'        => $clase['totalWeightKg'] ?? 0
            ]);

            // Promedios por clase
            if (!empty($clase['averageValues'])) {
                foreach ($clase['averageValues'] as $prom) {
                    $this->promedioClaseModelo->crear([
                        'detalle_clase_id' => $detalleId,
                        'criterio'         => $prom['criteria'] ?? '',
                        'regla'            => $prom['rule'] ?? null,
                        'puntaje_promedio' => $prom['avgScore'] ?? 0
                    ]);
                }
            }
        }
    }

    // -----------------------------------------------------------------
    // PERSISTIR ESTADÍSTICAS (reutilizable desde sincronización)
    // Trae classCounts de la API y guarda un nuevo snapshot.
    // Devuelve array con conteo_id y datos crudos.
    // Lanza Exception si falla.
    // -----------------------------------------------------------------
    public function persistirEstadisticas(int $loteId): array
    {
        $lote = $this->modelo->obtenerPorId($loteId);
        if (!$lote) {
            throw new Exception('Lote no encontrado.');
        }
        $linea = $this->lineaModelo->obtenerPorId((int) $lote['linea_id']);
        if (!$linea) {
            throw new Exception('La línea asociada al lote no existe.');
        }

        $cliente = new UnitecApiClient();
        $datos = $cliente->obtener_conteos_clase($linea['linea'], $lote['nombre_lote']);

        $usuario = Auth::usuarioActual();
        $usuarioId = $usuario['usuario_id'] ?? null;

        $conteoId = $this->conteoModelo->crear([
            'lote_id'             => $loteId,
            'codigo_externo_lote' => $datos['lotExternalCode'] ?? null,
            'created_by'          => $usuarioId,
            'updated_by'          => $usuarioId
        ]);

        if (!empty($datos['productionClassCounts'])) {
            $this->guardarDetallesClase($conteoId, 'production', $datos['productionClassCounts']);
        }
        if (!empty($datos['accountingClassCounts'])) {
            $this->guardarDetallesClase($conteoId, 'accounting', $datos['accountingClassCounts']);
        }
        if (!empty($datos['wasteCounts'])) {
            foreach ($datos['wasteCounts'] as $desecho) {
                $this->desechoModelo->crear([
                    'conteo_lote_id'      => $conteoId,
                    'descripcion_desecho' => $desecho['wasteDescription'] ?? null,
                    'tipo_desecho'        => $desecho['wasteType'] ?? 'PreSelection',
                    'peso_total_kg'       => $desecho['totalWeightKg'] ?? 0
                ]);
            }
        }
        if (!empty($datos['averageValues'])) {
            foreach ($datos['averageValues'] as $prom) {
                $this->promedioLoteModelo->crear([
                    'conteo_lote_id'   => $conteoId,
                    'criterio'         => $prom['criteria'] ?? '',
                    'regla'            => $prom['rule'] ?? null,
                    'puntaje_promedio' => $prom['avgScore'] ?? 0
                ]);
            }
        }

        return [
            'conteo_id' => $conteoId,
            'datos_api' => $datos
        ];
    }

    private function leerJson(): array
    {
        $cuerpo = file_get_contents('php://input');
        $datos = json_decode($cuerpo, true);
        if (!is_array($datos)) {
            Response::error('Datos inválidos en la petición.', 400);
        }
        return $datos;
    }

    private function validar(array $datos, array $requeridos): void
    {
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo]) && $datos[$campo] !== 0 && $datos[$campo] !== '0') {
                Response::error("El campo '$campo' es obligatorio.", 400);
            }
        }
    }
}
