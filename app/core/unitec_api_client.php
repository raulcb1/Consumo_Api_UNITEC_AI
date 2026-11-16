<?php
require_once __DIR__ . '/../models/equipo_api.php';
require_once __DIR__ . '/../models/registro_sincronizacion.php';

class UnitecApiClient
{
    private string $urlBase;
    private string $apiKey;
    private int $tiempoEspera;
    private int $equipoId;
    private RegistroSincronizacion $registroSync;

    public function __construct(?int $equipoId = null)
    {
        if ($equipoId === null) {
            $equipoId = $_SESSION['equipo_id'] ?? 1;
        }

        $equipoModelo = new EquipoApi();
        $equipo = $equipoModelo->obtenerPorId($equipoId);

        if (!$equipo) {
            throw new Exception("El equipo con ID $equipoId no existe.");
        }

        $this->equipoId     = $equipoId;
        $this->urlBase      = rtrim($equipo['url_base'], '/');
        $this->apiKey       = $equipo['api_key'];
        $this->tiempoEspera = (int) $equipo['tiempo_espera_segundos'];

        $this->registroSync = new RegistroSincronizacion();
    }

    public function obtener_equipo_id(): int
    {
        return $this->equipoId;
    }

    private function peticion(string $metodo, string $endpoint, array $queryParams = []): array
    {
        $url = $this->urlBase . $endpoint;
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $inicio = microtime(true);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->tiempoEspera);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);

        $cabeceras = [
            'X-API-KEY: ' . $this->apiKey,
            'Accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $cabeceras);

        $respuesta  = curl_exec($ch);
        $codigoHttp = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl  = curl_error($ch);
        //curl_close($ch);

        $tiempoMs = (int) ((microtime(true) - $inicio) * 1000);
        $datosDecodificados = json_decode($respuesta, true);

        $this->registroSync->registrar(
            $endpoint,
            $metodo,
            null,
            $codigoHttp > 0 ? $codigoHttp : null,
            $datosDecodificados,
            $errorCurl ?: null,
            $tiempoMs
        );

        if ($errorCurl) {
            throw new Exception("Error de conexión a la API UNITEC: $errorCurl");
        }
        if ($codigoHttp < 200 || $codigoHttp >= 300) {
            $detalle = $datosDecodificados['detail'] ?? $datosDecodificados['title'] ?? "Error HTTP $codigoHttp";
            throw new Exception("La API devolvió un error ($codigoHttp): $detalle");
        }

        return $datosDecodificados ?? [];
    }

    // =================================================================
    // NUEVOS ENDPOINTS (v2)
    // =================================================================

    public function obtener_lineas(): array
    {
        return $this->peticion('GET', '/api/lines');
    }

    public function obtener_lotes(string $linea): array
    {
        return $this->peticion('GET', "/api/lines/lots/{$linea}");
    }

    public function obtener_conteos(string $linea, int $lotId): array
    {
        return $this->peticion('GET', "/api/lines/counts/{$linea}/{$lotId}");
    }

    public function obtener_clases(string $linea, int $lotId): array
    {
        return $this->peticion('GET', "/api/lines/classes/{$linea}/{$lotId}");
    }
}
