<?php
// =====================================================================
// programascontroller.php - Controlador de programas de procesamiento
// =====================================================================
// Ubicación: app/controllers/programascontroller.php
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../models/programa.php';
require_once __DIR__ . '/../models/linea.php';
require_once __DIR__ . '/../models/registros_auditoria.php';

class ProgramasController
{
    private Programa $modelo;
    private Linea $lineaModelo;
    private RegistroAuditoria $auditoria;

    public function __construct()
    {
        Auth::requiereSesion();
        $this->modelo      = new Programa();
        $this->lineaModelo = new Linea();
        $this->auditoria   = new RegistroAuditoria();
    }

    // -----------------------------------------------------------------
    // LISTAR - GET
    // Acepta filtro opcional por linea_id (query string)
    // -----------------------------------------------------------------
    public function listar(): void
    {
        if (!Auth::tienePermiso('programas', 'ver')) {
            Response::error('No tienes permiso para ver los programas.', 403);
        }

        $lineaId = isset($_GET['linea_id']) ? (int) $_GET['linea_id'] : 0;

        if ($lineaId > 0) {
            $programas = $this->modelo->listarPorLinea($lineaId);
        } else {
            $programas = $this->modelo->listar();
        }

        Response::exito($programas, 'Listado de programas');
    }

    // -----------------------------------------------------------------
    // OBTENER POR ID - GET
    // -----------------------------------------------------------------
    public function obtener(int $id): void
    {
        if (!Auth::tienePermiso('programas', 'ver')) {
            Response::error('No tienes permiso para ver el programa.', 403);
        }
        $programa = $this->modelo->obtenerPorId($id);
        if (!$programa) {
            Response::error('Programa no encontrado.', 404);
        }
        Response::exito($programa, 'Detalle del programa');
    }

    // -----------------------------------------------------------------
    // CREAR - POST
    // -----------------------------------------------------------------
    public function crear(): void
    {
        if (!Auth::tienePermiso('programas', 'crear')) {
            Response::error('No tienes permiso para crear programas.', 403);
        }

        $datos = $this->leerJson();
        $this->validar($datos, ['programa_id_api', 'nombre', 'nombre_lista_clases', 'tipo', 'linea_id']);

        // Validar tipo permitido
        if (!in_array($datos['tipo'], ['Production', 'Accounting'], true)) {
            Response::error("El campo 'tipo' debe ser 'Production' o 'Accounting'.", 400);
        }

        // Validar que la línea exista
        if (!$this->lineaModelo->obtenerPorId((int) $datos['linea_id'])) {
            Response::error('La línea indicada no existe.', 404);
        }

        // Evitar duplicados (programa_id_api + linea_id)
        if ($this->modelo->obtenerPorIdApi((int) $datos['programa_id_api'], (int) $datos['linea_id'])) {
            Response::error('Ya existe un programa con ese ID en esa línea.', 409);
        }

        $usuario = Auth::usuarioActual();
        $datos['created_by'] = $usuario['usuario_id'];
        $datos['updated_by'] = $usuario['usuario_id'];

        $id = $this->modelo->crear($datos);
        if (!$id) {
            Response::error('No se pudo crear el programa.', 500);
        }

        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'programas',
            $id,
            'INSERT',
            null,
            $this->modelo->obtenerPorId($id)
        );

        Response::exito($this->modelo->obtenerPorId($id), 'Programa creado correctamente');
    }

    // -----------------------------------------------------------------
    // ACTUALIZAR - POST
    // -----------------------------------------------------------------
    public function actualizar(int $id): void
    {
        if (!Auth::tienePermiso('programas', 'editar')) {
            Response::error('No tienes permiso para editar programas.', 403);
        }

        $programa = $this->modelo->obtenerPorId($id);
        if (!$programa) {
            Response::error('Programa no encontrado.', 404);
        }

        $datos = $this->leerJson();
        $this->validar($datos, ['nombre', 'nombre_lista_clases', 'tipo']);

        if (!in_array($datos['tipo'], ['Production', 'Accounting'], true)) {
            Response::error("El campo 'tipo' debe ser 'Production' o 'Accounting'.", 400);
        }

        $usuario = Auth::usuarioActual();
        $datos['updated_by'] = $usuario['usuario_id'];

        if (!$this->modelo->actualizar($id, $datos)) {
            Response::error('No se pudo actualizar el programa.', 500);
        }

        $programaActualizado = $this->modelo->obtenerPorId($id);

        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'programas',
            $id,
            'UPDATE',
            $programa,
            $programaActualizado
        );

        Response::exito($programaActualizado, 'Programa actualizado correctamente');
    }

    // -----------------------------------------------------------------
    // ELIMINAR (SOFT DELETE) - POST
    // -----------------------------------------------------------------
    public function eliminar(int $id): void
    {
        if (!Auth::tienePermiso('programas', 'eliminar')) {
            Response::error('No tienes permiso para eliminar programas.', 403);
        }

        $programa = $this->modelo->obtenerPorId($id);
        if (!$programa) {
            Response::error('Programa no encontrado.', 404);
        }

        if (!$this->modelo->eliminar($id)) {
            Response::error('No se pudo eliminar el programa.', 500);
        }

        $usuario = Auth::usuarioActual();

        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'programas',
            $id,
            'SOFT_DELETE',
            $programa,
            null
        );

        Response::exito(null, 'Programa eliminado correctamente');
    }

    // -----------------------------------------------------------------
    // Helpers privados
    // -----------------------------------------------------------------
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