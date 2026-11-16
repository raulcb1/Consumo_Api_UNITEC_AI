<?php
require_once __DIR__ . '/../core/modelo.php';

class RegistroSincronizacion extends Modelo
{
    protected $tabla = 'registros_sincronizacion';

    /**
     * Registra una llamada HTTP a la API UNITEC.
     */
    public function registrar(
        string $endpoint,
        string $metodo,
        ?array $datosEnviados,
        ?int $codigoRespuesta,
        ?array $datosRespuesta,
        ?string $mensajeError,
        ?int $tiempoMs
    ): bool {
        return (bool) $this->crear([
            'endpoint'            => $endpoint,
            'metodo'              => $metodo,
            'datos_enviados'      => $datosEnviados ? json_encode($datosEnviados, JSON_UNESCAPED_UNICODE) : null,
            'codigo_respuesta'    => $codigoRespuesta,
            'datos_respuesta'     => $datosRespuesta ? json_encode($datosRespuesta, JSON_UNESCAPED_UNICODE) : null,
            'mensaje_error'       => $mensajeError,
            'tiempo_ejecucion_ms' => $tiempoMs
        ]);
    }

    public function listarRecientes(int $limite = 50): array
    {
        return $this->consultar(
            "SELECT * FROM registros_sincronizacion 
             ORDER BY created_at DESC 
             LIMIT $limite"
        );
    }

    public function listarPorEndpoint(string $endpoint, int $limite = 50): array
    {
        return $this->consultar(
            "SELECT * FROM registros_sincronizacion 
             WHERE endpoint = :endpoint 
             ORDER BY created_at DESC 
             LIMIT $limite",
            ['endpoint' => $endpoint]
        );
    }
}