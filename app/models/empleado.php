<?php
require_once __DIR__ . '/../core/modelo.php';

class Empleado extends Modelo
{
    protected $tabla = 'empleados';

    public function obtenerPorEmail(string $email)
    {
        return $this->consultarUna(
            "SELECT * FROM empleados WHERE email = :email AND deleted_at IS NULL",
            ['email' => $email]
        );
    }

    public function buscarPorNombre(string $termino): array
    {
        $like = '%' . $termino . '%';
        return $this->consultar(
            "SELECT * FROM empleados 
             WHERE (nombre LIKE :t1 OR apellido LIKE :t2) 
               AND deleted_at IS NULL 
             ORDER BY nombre ASC",
            ['t1' => $like, 't2' => $like]
        );
    }
}