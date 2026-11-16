<?php
// app/models/usuario.php

require_once __DIR__ . '/../core/modelo.php';

class Usuario extends Modelo
{
    protected $table = 'usuarios_sistema';

    /**
     * Obtener un usuario por su nombre de usuario, con roles.
     */
    public function obtenerPorUsuario(string $usuario): ?array
    {
        $sql = "SELECT us.*, r.nombre AS rol_nombre, e.nombre AS empleado_nombre
                FROM usuarios_sistema us
                LEFT JOIN roles r ON r.id = us.rol_id
                LEFT JOIN empleados e ON e.id = us.empleado_id
                WHERE us.usuario = :usuario
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Verificar si la contraseña es correcta.
     */
    public function verificarPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Crear un usuario con contraseña hasheada.
     */
    public function crearConHash(array $datos): int|false
    {
        if (isset($datos['password'])) {
            $datos['password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            unset($datos['password']);
        }
        return $this->crear($datos);
    }
}