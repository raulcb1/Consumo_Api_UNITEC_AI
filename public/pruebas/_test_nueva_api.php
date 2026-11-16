<?php
// public/_test_nueva_api.php

// 1. CONFIGURACIÓN DE LA NUEVA RED
$baseUrl = 'http://192.168.1.121:8080'; // IP corporativa con el puerto redirigido
$apiKey  = '2812-DA6E-826F-D6F4'; // <--- COLOCA AQUÍ LA CLAVE REAL QUE TE ENVIARON

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Prueba de Nueva API UNITEC</h1>";
echo "<p><strong>URL Base:</strong> $baseUrl</p>";
echo "<hr>";

// Función genérica para hacer las peticiones
function probarApi($url, $apiKey) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . $apiKey,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => "Error cURL: " . $error];
    }

    $datos = json_decode($response, true);
    return [
        'http_code' => $httpCode,
        'datos' => $datos,
        'raw' => $response
    ];
}

// -----------------------------------------------------------------
// PASO 1: Obtener Líneas
// Endpoint: GET /api/lines
// -----------------------------------------------------------------
echo "<h2>1. Obteniendo Líneas (/api/lines)</h2>";
$resLineas = probarApi("$baseUrl/api/lines", $apiKey);
echo "<p>Código HTTP: <strong>" . $resLineas['http_code'] . "</strong></p>";
echo "<pre>" . print_r($resLineas['datos'] ?? $resLineas['raw'], true) . "</pre>";

// Si falló, detenemos aquí para que revises la API Key o el Firewall
if ($resLineas['http_code'] !== 200 || empty($resLineas['datos'])) {
    die("<h3 style='color:red;'>❌ No se pudieron obtener las líneas. Verifica la API Key y la conexión de red.</h3>");
}

// Tomamos la primera línea para seguir probando
// Asumimos que la respuesta es un array de objetos con una propiedad 'line' o 'id'
$primeraLinea = is_array($resLineas['datos']) ? $resLineas['datos'][0] : $resLineas['datos'];
$lineId = $primeraLinea['line'] ?? $primeraLinea['id'] ?? $primeraLinea['name'] ?? '1'; 
//$lineId = 43;
echo "<p style='color:green;'>➡️ Usando Línea para pruebas: <strong>$lineId</strong></p>";
echo "<hr>";

// -----------------------------------------------------------------
// PASO 2: Obtener Lotes de esa Línea
// Endpoint: GET /api/lines/lots/{line}
// -----------------------------------------------------------------
echo "<h2>2. Obteniendo Lotes (/api/lines/lots/$lineId)</h2>";
$resLotes = probarApi("$baseUrl/api/lines/lots/$lineId", $apiKey);
echo "<p>Código HTTP: <strong>" . $resLotes['http_code'] . "</strong></p>";
echo "<pre>" . print_r($resLotes['datos'] ?? $resLotes['raw'], true) . "</pre>";

if ($resLotes['http_code'] !== 200 || empty($resLotes['datos'])) {
    die("<h3 style='color:orange;'>⚠️ La línea $lineId no tiene lotes o la ruta no es correcta.</h3>");
}

// Tomamos el primer lote para seguir probando
$primerLote = is_array($resLotes['datos']) ? $resLotes['datos'][0] : $resLotes['datos'];
$lotId = $primerLote['id'] ?? $primerLote['lotId'] ?? $primerLote['name'] ?? '1';
$lotId = 1056;
echo "<p style='color:green;'>➡️ Usando LoteId para pruebas: <strong>$lotId</strong></p>";
echo "<hr>";

// -----------------------------------------------------------------
// PASO 3: Obtener Conteos (Counts)
// Endpoint: GET /api/lines/counts/{line}/{lotId}
// -----------------------------------------------------------------
echo "<h2>3. Obteniendo Conteos (/api/lines/counts/$lineId/$lotId)</h2>";
$resCounts = probarApi("$baseUrl/api/lines/counts/$lineId/$lotId", $apiKey);
echo "<p>Código HTTP: <strong>" . $resCounts['http_code'] . "</strong></p>";
echo "<pre>" . print_r($resCounts['datos'] ?? $resCounts['raw'], true) . "</pre>";
echo "<hr>";

// -----------------------------------------------------------------
// PASO 4: Obtener Clases (Classes)
// Endpoint: GET /api/lines/classes/{line}/{lotId}
// -----------------------------------------------------------------
echo "<h2>4. Obteniendo Clases (/api/lines/classes/$lineId/$lotId)</h2>";
$resClasses = probarApi("$baseUrl/api/lines/classes/$lineId/$lotId", $apiKey);
echo "<p>Código HTTP: <strong>" . $resClasses['http_code'] . "</strong></p>";
echo "<pre>" . print_r($resClasses['datos'] ?? $resClasses['raw'], true) . "</pre>";

echo "<hr><h3>✅ Fin de las pruebas</h3>";