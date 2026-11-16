<?php
// public/_test_nueva_api_v2.php

$baseUrl = 'http://192.168.1.121:8080';
$apiKey  = '2812-DA6E-826F-D6F4'; // <--- COLOCA AQUÍ LA CLAVE REAL
$linea   = '1';    // Línea obtenida en la prueba anterior
$lotId   = '1013'; // LoteId obtenido en la prueba anterior

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Escaneo de Endpoints - Nueva API UNITEC (v2)</h1>";
echo "<p><strong>URL Base:</strong> $baseUrl</p>";
echo "<hr>";

// Función para hacer peticiones
function probarEndpoint($metodo, $url, $apiKey, $body = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);

    $headers = [
        'X-API-KEY: ' . $apiKey,
        'Accept: application/json'
    ];

    if ($metodo === 'POST') {
        $json = json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return ['error' => $error, 'code' => 0, 'data' => ''];

    $datos = json_decode($response, true);
    return [
        'code' => $httpCode,
        'data' => $datos,
        'raw' => substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '') // Resumen
    ];
}

// Función para imprimir resultados
function mostrarResultado($titulo, $res)
{
    echo "<div style='margin-bottom: 20px; padding: 10px; border-left: 4px solid #ccc; background: #f9f9f9;'>";
    echo "<h3>$titulo</h3>";

    if (isset($res['error'])) {
        echo "<p style='color:red;'>❌ Error de conexión: " . $res['error'] . "</p>";
    } else {
        $color = in_array($res['code'], [200, 201, 204]) ? 'green' : ($res['code'] == 404 ? 'red' : 'orange');
        echo "<p><strong>Código HTTP:</strong> <span style='color:$color; font-size:1.2em;'>" . $res['code'] . "</span></p>";

        if ($res['code'] == 404) {
            echo "<p>Ruta no encontrada (El endpoint no existe).</p>";
        } elseif ($res['code'] == 405) {
            echo "<p>Método no permitido (La ruta existe, pero no acepta este método HTTP).</p>";
        } else {
            echo "<p>Respuesta (resumen):</p>";
            echo "<pre style='max-height: 200px; overflow-y: auto; background: #fff; padding: 10px; border: 1px solid #ddd;'>" . print_r($res['data'] ?? $res['raw'], true) . "</pre>";
        }
    }
    echo "</div>";
}

// =================================================================
// 1. BUSCAR SWAGGER (Documentación)
// =================================================================
echo "<h2>1. Búsqueda de Documentación (Swagger)</h2>";
$swaggerPaths = [
    '/swagger.json',
    '/api/swagger.json',
    '/api/swagger/v1/swagger.json',
    '/v1/swagger.json'
];
foreach ($swaggerPaths as $path) {
    $res = probarEndpoint('GET', $baseUrl . $path, $apiKey);
    if ($res['code'] == 200) {
        echo "<h3 style='color:green;'>✅ ¡Swagger encontrado en $path!</h3>";
        echo "<p>Revisa la pestaña de Red de tu navegador o accede a <a href='{$baseUrl}{$path}' target='_blank'>{$baseUrl}{$path}</a> para ver la documentación.</p>";
    }
}
echo "<hr>";

// =================================================================
// 2. ENDPOINTS ANTIGUOS (¿Siguen existiendo?)
// =================================================================
echo "<h2>2. Prueba de Endpoints Antiguos (Legacy)</h2>";

// A. Programas (Antiguo: /api/Lines/{line}/Programs)
mostrarResultado(
    "GET /api/Lines/{line}/Programs (Antiguo)",
    probarEndpoint('GET', "$baseUrl/api/Lines/$linea/Programs", $apiKey)
);

// B. Programas (Nuevo intento en minúsculas)
mostrarResultado(
    "GET /api/lines/programs/{line} (Nuevo)",
    probarEndpoint('GET', "$baseUrl/api/lines/programs/$linea", $apiKey)
);

// C. Crear Lote (Antiguo: POST /api/Lines/{line}/Lots)
$bodyLote = [
    'name' => 'TEST-SCRIPT-' . time(),
    'productionProgramId' => 1
];
mostrarResultado(
    "POST /api/Lines/{line}/Lots (Crear - Antiguo)",
    probarEndpoint('POST', "$baseUrl/api/Lines/$linea/Lots", $apiKey, $bodyLote)
);

// D. Crear Lote (Nuevo intento en minúsculas)
mostrarResultado(
    "POST /api/lines/lots/{line} (Crear - Nuevo)",
    probarEndpoint('POST', "$baseUrl/api/lines/lots/$linea", $apiKey, $bodyLote)
);

// E. Iniciar Lote (Antiguo: /run)
mostrarResultado(
    "POST /api/Lines/{line}/Lots/{lotId}/run (Iniciar)",
    probarEndpoint('POST', "$baseUrl/api/Lines/$linea/Lots/$lotId/run", $apiKey)
);

// F. Detener Lote (Antiguo: /stop)
mostrarResultado(
    "POST /api/Lines/{line}/Lots/{lotId}/stop (Detener)",
    probarEndpoint('POST', "$baseUrl/api/Lines/$linea/Lots/$lotId/stop", $apiKey)
);

// G. Detalle de un Lote Específico (Antiguo: /api/Lines/{line}/Lots/{lotName})
mostrarResultado(
    "GET /api/lines/lots/{line}/{lotId} (Nuevo intento)",
    probarEndpoint('GET', "$baseUrl/api/lines/lots/$linea/$lotId", $apiKey)
);

echo "<hr><h3>✅ Fin de las pruebas</h3>";
