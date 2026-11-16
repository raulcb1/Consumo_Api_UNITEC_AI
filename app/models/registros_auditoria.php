<?php
require_once __DIR__ . '/../core/modelo.php';

class RegistroAuditoria extends Modelo
{
    protected $tabla = 'registros_auditoria';

    /**
     * Registra una acción en la auditoría del sistema.
     */
    public function registrar(
        ?int $usuarioId,
        string $tabla,
        int $registroId,
        string $accion,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): bool {
        return (bool) $this->crear([
            'usuario_id'       => $usuarioId,
            'tabla'            => $tabla,
            'registro_id'      => $registroId,
            'accion'           => $accion,
            'datos_anteriores' => $datosAnteriores ? json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE) : null,
            'datos_nuevos'     => $datosNuevos ? json_encode($datosNuevos, JSON_UNESCAPED_UNICODE) : null,
            'ip'               => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'       => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    public function listarPaginado(int $limite = 100, int $offset = 0, ?string $tablaFiltro = null): array
    {
        $sql = "SELECT a.*, u.usuario 
                FROM registros_auditoria a
                LEFT JOIN usuarios_sistema u ON a.usuario_id = u.id";
        $params = [];
        if ($tablaFiltro) {
            $sql .= " WHERE a.tabla = :tabla";
            $params['tabla'] = $tablaFiltro;
        }
        $sql .= " ORDER BY a.created_at DESC LIMIT $limite OFFSET $offset";
        return $this->consultar($sql, $params);
    }

    public function contarTodos(?string $tablaFiltro = null): int
    {
        $sql = "SELECT COUNT(*) AS total FROM registros_auditoria";
        $params = [];
        if ($tablaFiltro) {
            $sql .= " WHERE tabla = :tabla";
            $params['tabla'] = $tablaFiltro;
        }
        $fila = $this->consultarUna($sql, $params);
        return (int) ($fila['total'] ?? 0);
    }
}