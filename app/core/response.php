<?php
// =====================================================================
// response.php - Respuestas JSON unificadas para el frontend
// =====================================================================

class Response
{
    /**
     * Devuelve una respuesta exitosa (HTTP 200) y termina la ejecución.
     */
    public static function exito($datos = null, string $mensaje = 'Operación exitosa'): never
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'data'    => $datos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Devuelve una respuesta de error y termina la ejecución.
     */
    public static function error(string $mensaje, int $codigo = 400, $datos = null): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $mensaje,
            'data'    => $datos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Devuelve una respuesta sin contenido (HTTP 204) y termina la ejecución.
     */
    public static function sinContenido(): never
    {
        http_response_code(204);
        exit;
    }
}