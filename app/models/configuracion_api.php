<?php
require_once __DIR__ . '/../core/modelo.php';

class ConfiguracionApi extends Modelo
{
    protected $tabla = 'configuracion_api';

    public function obtenerValor(string $clave): ?string
    {
        $fila = $this->consultarUna(
            "SELECT valor FROM configuracion_api WHERE clave = :clave",
            ['clave' => $clave]
        );
        return $fila ? $fila['valor'] : null;
    }

    public function listarTodo(): array
    {
        return $this->consultar(
            "SELECT * FROM configuracion_api ORDER BY clave ASC"
        );
    }

    public function actualizarValor(string $clave, string $valor): bool
    {
        return (bool) $this->ejecutar(
            "UPDATE configuracion_api SET valor = :valor WHERE clave = :clave",
            ['clave' => $clave, 'valor' => $valor]
        );
    }
}