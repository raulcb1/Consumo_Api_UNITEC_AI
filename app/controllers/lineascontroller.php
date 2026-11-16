<?php
// =====================================================================
// lineascontroller.php - Controlador de líneas de producción
// =====================================================================
// Ubicación: app/controllers/lineascontroller.php
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../models/linea.php';
require_once __DIR__ . '/../models/registros_auditoria.php';

class LineasController
{
    private Linea $modelo;
    private RegistroAuditoria $auditoria;

    public function __construct()
    {
        Auth::requiereSesion();
        $this->modelo    = new Linea();
        $this->auditoria = new RegistroAuditoria();
    }

    // -----------------------------------------------------------------
    // LISTAR - GET
    // -----------------------------------------------------------------
    public function listar(): void
    {
        if (!Auth::tienePermiso('lineas', 'ver')) {
            Response::error('No tienes permiso para ver las líneas.', 403);
        }
        $lineas = $this->modelo->listarActivas();
        Response::exito($lineas, 'Listado de líneas');
    }

    // -----------------------------------------------------------------
    // OBTENER POR ID - GET
    // -----------------------------------------------------------------
    public function obtener(int $id): void
    {
        if (!Auth::tienePermiso('lineas', 'ver')) {
            Response::error('No tienes permiso para ver la línea.', 403);
        }
        $linea = $this->modelo->obtenerPorId($id);
        if (!$linea) {
            Response::error('Línea no encontrada.', 404);
        }
        Response::exito($linea, 'Detalle de la línea');
    }

    // -----------------------------------------------------------------
    // CREAR - POST
    // -----------------------------------------------------------------
    public function crear(): void
    {
        if (!Auth::tienePermiso('lineas', 'crear')) {
            Response::error('No tienes permiso para crear líneas.', 403);
        }

        $datos = $this->leerJson();
        $this->validar($datos, ['linea']);

        // Evitar duplicados
        if ($this->modelo->obtenerPorCodigo($datos['linea'])) {
            Response::error('Ya existe una línea con ese código.', 409);
        }

        $usuario = Auth::usuarioActual();
        $datos['created_by'] = $usuario['usuario_id'];
        $datos['updated_by'] = $usuario['usuario_id'];

        $id = $this->modelo->crear($datos);
        if (!$id) {
            Response::error('No se pudo crear la línea.', 500);
        }

        // Auditoría
        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lineas',
            $id,
            'INSERT',
            null,
            $this->modelo->obtenerPorId($id)
        );

        Response::exito($this->modelo->obtenerPorId($id), 'Línea creada correctamente');
    }

    // -----------------------------------------------------------------
    // ACTUALIZAR - POST
    // -----------------------------------------------------------------
    public function actualizar(int $id): void
    {
        if (!Auth::tienePermiso('lineas', 'editar')) {
            Response::error('No tienes permiso para editar líneas.', 403);
        }

        $linea = $this->modelo->obtenerPorId($id);
        if (!$linea) {
            Response::error('Línea no encontrada.', 404);
        }

        $datos = $this->leerJson();
        $this->validar($datos, ['linea']);

        $usuario = Auth::usuarioActual();
        $datos['updated_by'] = $usuario['usuario_id'];

        if (!$this->modelo->actualizar($id, $datos)) {
            Response::error('No se pudo actualizar la línea.', 500);
        }

        $lineaActualizada = $this->modelo->obtenerPorId($id);

        // Auditoría
        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lineas',
            $id,
            'UPDATE',
            $linea,
            $lineaActualizada
        );

        Response::exito($lineaActualizada, 'Línea actualizada correctamente');
    }

    // -----------------------------------------------------------------
    // ELIMINAR (SOFT DELETE) - POST
    // -----------------------------------------------------------------
    public function eliminar(int $id): void
    {
        if (!Auth::tienePermiso('lineas', 'eliminar')) {
            Response::error('No tienes permiso para eliminar líneas.', 403);
        }

        $linea = $this->modelo->obtenerPorId($id);
        if (!$linea) {
            Response::error('Línea no encontrada.', 404);
        }

        if (!$this->modelo->eliminar($id)) {
            Response::error('No se pudo eliminar la línea.', 500);
        }

        $usuario = Auth::usuarioActual();

        // Auditoría
        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lineas',
            $id,
            'SOFT_DELETE',
            $linea,
            null
        );

        Response::exito(null, 'Línea eliminada correctamente');
    }

    public function restaurar(int $id): void
    {
        if (!Auth::tienePermiso('lineas', 'editar')) {
            Response::error('No tienes permiso para restaurar líneas.', 403);
        }

        if (!$this->modelo->restaurar($id)) {
            Response::error('No se pudo restaurar la línea.', 500);
        }

        $usuario = Auth::usuarioActual();
        $this->auditoria->registrar(
            $usuario['usuario_id'],
            'lineas',
            $id,
            'RESTORE',
            null,
            $this->modelo->obtenerPorId($id)
        );

        Response::exito($this->modelo->obtenerPorId($id), 'Línea restaurada correctamente');
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
            if (empty($datos[$campo])) {
                Response::error("El campo '$campo' es obligatorio.", 400);
            }
        }
    }
}
