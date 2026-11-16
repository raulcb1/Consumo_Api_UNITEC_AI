<?php
require_once __DIR__ . '/../core/modelo.php';

class UsuarioSistema extends Modelo
{
    protected $tabla = 'usuarios_sistema';

    public function obtenerPorUsuario(string $usuario)
    {
        return $this->consultarUna(
            "SELECT us.*, r.nombre AS rol, e.nombre AS empleado_nombre
             FROM usuarios_sistema us
             JOIN roles r ON r.id = us.rol_id
             LEFT JOIN empleados e ON e.id = us.empleado_id
             WHERE us.usuario = :usuario AND us.deleted_at IS NULL",
            ['usuario' => $usuario]
        );
    }

    public function listarConRolYEmpleado(): array
    {
        return $this->consultar(
            "SELECT us.id, us.usuario, us.activo, us.fecha_expiracion,
                    us.ultimo_acceso, us.created_at,
                    r.nombre AS rol, e.nombre AS empleado_nombre, e.apellido AS empleado_apellido
             FROM usuarios_sistema us
             JOIN roles r ON r.id = us.rol_id
             LEFT JOIN empleados e ON e.id = us.empleado_id
             WHERE us.deleted_at IS NULL
             ORDER BY us.id DESC"
        );
    }

    public function crearConHash(array $datos)
    {
        if (isset($datos['password'])) {
            $datos['password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            unset($datos['password']);
        }
        return $this->crear($datos);
    }

    public function actualizarConHash(int $id, array $datos): bool
    {
        if (!empty($datos['password'])) {
            $datos['password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }
        unset($datos['password']);
        return $this->actualizar($id, $datos);
    }

    public function registrarAcceso(int $id): bool
    {
        return (bool) $this->ejecutar(
            "UPDATE usuarios_sistema SET ultimo_acceso = NOW() WHERE id = :id",
            ['id' => $id]
        );
    }
}