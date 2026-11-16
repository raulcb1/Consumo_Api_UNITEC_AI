<?php
// app/controllers/configuracioncontroller.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../models/equipo_api.php';
require_once __DIR__ . '/../models/registros_auditoria.php';

class ConfiguracionController
{
    private EquipoApi $modelo;
    private RegistroAuditoria $auditoria;

    public function __construct()
    {
        Auth::requiereSesion();
        $this->modelo = new EquipoApi();
        $this->auditoria = new RegistroAuditoria();
    }

    public function listar(): void
    {
        if (!Auth::tienePermiso('configuracion_api', 'ver')) {
            Response::error('No tienes permiso.', 403);
        }
        $equipos = $this->modelo->listarTodos();
        $actual = $_SESSION['equipo_id'] ?? 1;

        Response::exito([
            'equipos' => $equipos,
            'equipo_actual' => $actual
        ], 'Listado de equipos');
    }

    public function obtener(int $id): void
    {
        if (!Auth::tienePermiso('configuracion_api', 'ver')) {
            Response::error('No tienes permiso.', 403);
        }
        $equipo = $this->modelo->obtenerPorId($id);
        if (!$equipo) {
            Response::error('Equipo no encontrado.', 404);
        }

        // Enmascarar API Key
        if (strlen($equipo['api_key']) > 6) {
            $equipo['api_key'] = substr($equipo['api_key'], 0, 3) . '...' . substr($equipo['api_key'], -3);
        }

        Response::exito($equipo, 'Detalle del equipo');
    }

    public function crear(): void
    {
        if (!Auth::tienePermiso('configuracion_api', 'editar')) {
            Response::error('No tienes permiso.', 403);
        }
        $datos = $this->leerJson();

        $id = $this->modelo->crear([
            'nombre' => $datos['nombre'],
            'url_base' => $datos['url_base'],
            'api_key' => $datos['api_key'],
            'linea_por_defecto' => $datos['linea_por_defecto'] ?? null,
            'tiempo_espera_segundos' => $datos['tiempo_espera_segundos'] ?? 20,
            'activo' => 1
        ]);

        $usuario = Auth::usuarioActual();
        $this->auditoria->registrar($usuario['usuario_id'], 'equipos_api', $id, 'INSERT', null, $datos);

        Response::exito(['id' => $id], 'Equipo creado correctamente.');
    }

    public function actualizar(int $id): void
    {
        if (!Auth::tienePermiso('configuracion_api', 'editar')) {
            Response::error('No tienes permiso.', 403);
        }
        $datos = $this->leerJson();
        $equipoActual = $this->modelo->obtenerPorId($id);
        if (!$equipoActual) {
            Response::error('Equipo no encontrado.', 404);
        }

        // Si la API Key viene enmascarada, mantenemos la original
        if (isset($datos['api_key']) && strpos($datos['api_key'], '...') !== false) {
            $datos['api_key'] = $equipoActual['api_key'];
        }

        $this->modelo->actualizar($id, [
            'nombre' => $datos['nombre'],
            'url_base' => $datos['url_base'],
            'api_key' => $datos['api_key'],
            'linea_por_defecto' => $datos['linea_por_defecto'] ?? null,
            'tiempo_espera_segundos' => $datos['tiempo_espera_segundos'] ?? 20
        ]);

        $usuario = Auth::usuarioActual();
        $this->auditoria->registrar($usuario['usuario_id'], 'equipos_api', $id, 'UPDATE', $equipoActual, $datos);

        Response::exito(null, 'Equipo actualizado correctamente.');
    }

    public function eliminar(int $id): void
    {
        if (!Auth::tienePermiso('configuracion_api', 'editar')) {
            Response::error('No tienes permiso.', 403);
        }

        $equipo = $this->modelo->obtenerPorId($id);
        if (!$equipo) {
            Response::error('Equipo no encontrado.', 404);
        }

        $this->modelo->eliminar($id);

        // Si estábamos usando este equipo, volvemos al 1
        if (($_SESSION['equipo_id'] ?? 1) == $id) {
            $_SESSION['equipo_id'] = 1;
        }

        Response::exito(null, 'Equipo eliminado correctamente.');
    }

    public function seleccionar(int $id): void
    {
        if (!Auth::tienePermiso('configuracion_api', 'editar')) {
            Response::error('No tienes permiso.', 403);
        }

        $equipo = $this->modelo->obtenerPorId($id);
        if (!$equipo) {
            Response::error('Equipo no encontrado.', 404);
        }

        $_SESSION['equipo_id'] = $id;

        Response::exito(null, 'Ahora está operando con el equipo: ' . $equipo['nombre']);
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
}
