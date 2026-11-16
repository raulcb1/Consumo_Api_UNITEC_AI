<?php
// =====================================================================
// usuarioscontroller.php - Controlador de usuarios del sistema
// =====================================================================
// Ubicación: app/controllers/usuarioscontroller.php
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../models/usuario_sistema.php';
require_once __DIR__ . '/../models/empleado.php';
require_once __DIR__ . '/../models/rol.php';
require_once __DIR__ . '/../models/registros_auditoria.php';

class UsuariosController
{
    private UsuarioSistema $modelo;
    private Empleado $empleadoModelo;
    private Rol $rolModelo;
    private RegistroAuditoria $auditoria;

    public function __construct()
    {
        Auth::requiereSesion();
        $this->modelo         = new UsuarioSistema();
        $this->empleadoModelo = new Empleado();
        $this->rolModelo      = new Rol();
        $this->auditoria      = new RegistroAuditoria();
    }

    // -----------------------------------------------------------------
    // LISTAR - GET
    // -----------------------------------------------------------------
    public function listar(): void
    {
        if (!Auth::tienePermiso('usuarios', 'ver')) {
            Response::error('No tienes permiso para ver los usuarios.', 403);
        }
        $usuarios = $this->modelo->listarConRolYEmpleado();
        Response::exito($usuarios, 'Listado de usuarios');
    }

    // -----------------------------------------------------------------
    // OBTENER POR ID - GET
    // -----------------------------------------------------------------
    public function obtener(int $id): void
    {
        if (!Auth::tienePermiso('usuarios', 'ver')) {
            Response::error('No tienes permiso para ver el usuario.', 403);
        }
        $usuario = $this->modelo->obtenerPorId($id);
        if (!$usuario) {
            Response::error('Usuario no encontrado.', 404);
        }
        // No devolver el hash
        unset($usuario['password_hash']);
        Response::exito($usuario, 'Detalle del usuario');
    }

    // -----------------------------------------------------------------
    // LISTAR ROLES - GET (para dropdown del formulario)
    // -----------------------------------------------------------------
    public function listarRoles(): void
    {
        if (!Auth::tienePermiso('usuarios', 'ver')) {
            Response::error('No tienes permiso para ver los roles.', 403);
        }
        $roles = $this->rolModelo->listar();
        Response::exito($roles, 'Listado de roles');
    }

    // -----------------------------------------------------------------
    // LISTAR EMPLEADOS - GET (para dropdown del formulario)
    // -----------------------------------------------------------------
    public function listarEmpleados(): void
    {
        if (!Auth::tienePermiso('usuarios', 'ver')) {
            Response::error('No tienes permiso para ver los empleados.', 403);
        }
        $empleados = $this->empleadoModelo->listar();
        Response::exito($empleados, 'Listado de empleados');
    }

    // -----------------------------------------------------------------
    // CREAR - POST
    // -----------------------------------------------------------------
    public function crear(): void
    {
        if (!Auth::tienePermiso('usuarios', 'crear')) {
            Response::error('No tienes permiso para crear usuarios.', 403);
        }

        $datos = $this->leerJson();
        $this->validar($datos, ['usuario', 'password', 'rol_id']);

        // Validar que no exista
        if ($this->modelo->obtenerPorUsuario($datos['usuario'])) {
            Response::error('Ya existe un usuario con ese nombre.', 409);
        }

        // Validar rol
        if (!$this->rolModelo->obtenerPorId((int) $datos['rol_id'])) {
            Response::error('El rol indicado no existe.', 404);
        }

        // Validar empleado si viene
        if (!empty($datos['empleado_id']) && !$this->empleadoModelo->obtenerPorId((int) $datos['empleado_id'])) {
            Response::error('El empleado indicado no existe.', 404);
        }

        $usuarioActual = Auth::usuarioActual();

        $id = $this->modelo->crearConHash([
            'usuario'          => $datos['usuario'],
            'password'         => $datos['password'],
            'activo'           => isset($datos['activo']) ? (int) $datos['activo'] : 1,
            'fecha_expiracion' => $datos['fecha_expiracion'] ?? null,
            'empleado_id'      => $datos['empleado_id'] ?? null,
            'rol_id'           => (int) $datos['rol_id']
        ]);

        if (!$id) {
            Response::error('No se pudo crear el usuario.', 500);
        }

        $usuarioCreado = $this->modelo->obtenerPorId($id);
        unset($usuarioCreado['password_hash']);

        $this->auditoria->registrar(
            $usuarioActual['usuario_id'],
            'usuarios_sistema',
            $id,
            'INSERT',
            null,
            $usuarioCreado
        );

        Response::exito($usuarioCreado, 'Usuario creado correctamente');
    }

    // -----------------------------------------------------------------
    // ACTUALIZAR - POST
    // -----------------------------------------------------------------
    public function actualizar(int $id): void
    {
        if (!Auth::tienePermiso('usuarios', 'editar')) {
            Response::error('No tienes permiso para editar usuarios.', 403);
        }

        $usuario = $this->modelo->obtenerPorId($id);
        if (!$usuario) {
            Response::error('Usuario no encontrado.', 404);
        }

        $datos = $this->leerJson();

        // Campos editables
        $permitidos = ['usuario', 'activo', 'fecha_expiracion', 'empleado_id', 'rol_id'];
        $actualizables = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $actualizables[$campo] = $datos[$campo];
            }
        }

        // Si envían password, se actualiza también
        if (!empty($datos['password'])) {
            $actualizables['password'] = $datos['password'];
        }

        if (empty($actualizables)) {
            Response::error('No hay campos válidos para actualizar.', 400);
        }

        // Validar rol si viene
        if (isset($actualizables['rol_id']) && !$this->rolModelo->obtenerPorId((int) $actualizables['rol_id'])) {
            Response::error('El rol indicado no existe.', 404);
        }

        $usuarioActual = Auth::usuarioActual();

        if (!$this->modelo->actualizarConHash($id, $actualizables)) {
            Response::error('No se pudo actualizar el usuario.', 500);
        }

        $usuarioActualizado = $this->modelo->obtenerPorId($id);
        unset($usuarioActualizado['password_hash']);
        unset($usuario['password_hash']);

        $this->auditoria->registrar(
            $usuarioActual['usuario_id'],
            'usuarios_sistema',
            $id,
            'UPDATE',
            $usuario,
            $usuarioActualizado
        );

        Response::exito($usuarioActualizado, 'Usuario actualizado correctamente');
    }

    // -----------------------------------------------------------------
    // ELIMINAR (SOFT DELETE) - POST
    // -----------------------------------------------------------------
    public function eliminar(int $id): void
    {
        if (!Auth::tienePermiso('usuarios', 'eliminar')) {
            Response::error('No tienes permiso para eliminar usuarios.', 403);
        }

        // Evitar que un usuario se elimine a sí mismo
        $usuarioActual = Auth::usuarioActual();
        if ((int) $usuarioActual['usuario_id'] === $id) {
            Response::error('No puedes eliminar tu propio usuario.', 400);
        }

        $usuario = $this->modelo->obtenerPorId($id);
        if (!$usuario) {
            Response::error('Usuario no encontrado.', 404);
        }

        if (!$this->modelo->eliminar($id)) {
            Response::error('No se pudo eliminar el usuario.', 500);
        }

        unset($usuario['password_hash']);

        $this->auditoria->registrar(
            $usuarioActual['usuario_id'],
            'usuarios_sistema',
            $id,
            'SOFT_DELETE',
            $usuario,
            null
        );

        Response::exito(null, 'Usuario eliminado correctamente');
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