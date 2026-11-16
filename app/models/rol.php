<?php
require_once __DIR__ . '/../core/modelo.php';

class Rol extends Modelo
{
    protected $tabla = 'roles';

    public function obtenerPorNombre(string $nombre)
    {
        return $this->consultarUna(
            "SELECT * FROM roles WHERE nombre = :nombre",
            ['nombre' => $nombre]
        );
    }
}