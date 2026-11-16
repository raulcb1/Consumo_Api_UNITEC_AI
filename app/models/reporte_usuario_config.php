<?php
require_once __DIR__ . '/../core/modelo.php';

class ReporteUsuarioConfig extends Modelo
{
    protected $tabla = 'reportes_usuario_config';
    protected $tieneSoftDelete = false;

    public function obtener(int $usuarioId, string $reporteCodigo): ?array
    {
        return $this->consultarUna(
            "SELECT * FROM reportes_usuario_config 
             WHERE usuario_id = :uid AND reporte_codigo = :cod",
            ['uid' => $usuarioId, 'cod' => $reporteCodigo]
        ) ?: null;
    }

    public function guardar(int $usuarioId, string $reporteCodigo, array $datos): bool
    {
        $existente = $this->obtener($usuarioId, $reporteCodigo);

        $payload = [
            'usuario_id'        => $usuarioId,
            'reporte_codigo'    => $reporteCodigo,
            'columnas_visibles' => isset($datos['columnas_visibles']) ? json_encode($datos['columnas_visibles']) : null,
            'columnas_orden'    => isset($datos['columnas_orden'])    ? json_encode($datos['columnas_orden'])    : null,
            'filtros_guardados' => isset($datos['filtros_guardados']) ? json_encode($datos['filtros_guardados']) : null,
            'es_favorito'       => !empty($datos['es_favorito']) ? 1 : 0,
        ];

        if ($existente) {
            return $this->actualizar((int) $existente['id'], $payload);
        }
        return (bool) $this->crear($payload);
    }

    public function listarFavoritos(int $usuarioId): array
    {
        return $this->consultar(
            "SELECT * FROM reportes_usuario_config 
             WHERE usuario_id = :uid AND es_favorito = 1",
            ['uid' => $usuarioId]
        );
    }
}
