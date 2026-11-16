<?php
// public/_scan_api.php

$baseUrl = 'http://192.168.1.121:8080';
$apiKey  = '2812-DA6E-826F-D6F4'; // <--- COLOCA AQUÍ TU CLAVE REAL

// Lista masiva de endpoints sospechosos basados en la UI y convenciones REST
$endpoints = [
    // --- 1. SISTEMA Y ESTADO ---
    '/api',
    '/api/version',
    '/api/status',
    '/api/health',
    '/api/ping',
    '/api/info',
    '/api/system',
    '/api/metrics',
    '/api/time',

    // --- 2. NOTIFICACIONES Y DIAGNÓSTICO (Vistos en pantallazos) ---
    '/api/notifications',
    '/api/events',
    '/api/alarms',
    '/api/warnings',
    '/api/history',
    '/api/notifications/history',
    '/api/logs',
    '/api/logs/system',
    '/api/diagnostics',
    '/api/diagnostic-report',
    '/api/errors',

    // --- 3. PROGRAMAS Y CONFIGURACIÓN ---
    '/api/programs',
    '/api/program',
    '/api/lines/programs',
    '/api/settings',
    '/api/config',
    '/api/configuration',
    '/api/parameters',
    '/api/references',
    '/api/calibration',
    '/api/settings/references',

    // --- 4. LOTES (Variaciones y detalles profundos) ---
    '/api/lots',
    '/api/lot',
    '/api/lines/lots/all',
    '/api/lines/1/lots/1072',
    '/api/lots/1072',
    '/api/lines/1/lots/1072/details',
    '/api/lots/1072/details',

    // --- 5. CONTEO Y MEDIDAS (Buscando más allá de /counts y /classes) ---
    '/api/lines/counts',
    '/api/counts',
    '/api/lines/1/counts',
    '/api/lines/measures',
    '/api/lines/1/measures',
    '/api/lines/1/lots/1072/measures',
    '/api/lots/1072/measures',
    '/api/lines/classes',
    '/api/classes',
    '/api/lines/1/classes',
    '/api/lines/1/lots/1072/classes',
    '/api/lots/1072/classes',
    '/api/lines/1/lots/1072/defects',
    '/api/lots/1072/defects',
    '/api/lines/1/lots/1072/quality',
    '/api/lots/1072/quality',
    '/api/lines/1/lots/1072/colors',
    '/api/lots/1072/colors',
    '/api/lines/1/lots/1072/sizes',
    '/api/lots/1072/sizes',

    // --- 6. REPORTES Y EXPORTACIÓN ---
    '/api/reports',
    '/api/lines/reports',
    '/api/export',
    '/api/excel',
    '/api/csv',

    // --- 7. USUARIOS Y SEGURIDAD ---
    '/api/users',
    '/api/auth',
    '/api/login',
    '/api/roles',
    '/api/permissions',

    // --- 8. CALIBRACIÓN Y CÁMARAS ---
    '/api/cameras',
    '/api/cameras/status',
    '/api/sensors',
    '/api/hyperspectral'
];

echo "<html><head><title>Escaneo UNITEC API</title><style>
    body { font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 20px; }
    .ok { color: #4ade80; font-weight: bold; }
    .auth { color: #fbbf24; font-weight: bold; }
    .method { color: #60a5fa; font-weight: bold; }
    .error { color: #f87171; }
    pre { background: #0f172a; padding: 10px; border-radius: 5px; margin: 5px 0 15px 0; white-space: pre-wrap; word-break: break-all;}
    li { margin-bottom: 10px; }
    hr { border-color: #334155; }
</style></head><body>";

echo "<h1>🕵️‍♂️ Escaneo de Endpoints UNITEC</h1>";
echo "<p>Probando " . count($endpoints) . " rutas...</p><hr>";

$encontrados = 0;

foreach ($endpoints as $endpoint) {
    $url = $baseUrl . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . $apiKey,
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Pequeña pausa de 100 milisegundos entre peticiones para no saturar la máquina
    usleep(100000);

    if ($httpCode == 200) {
        $encontrados++;
        echo "<li><span class='ok'>[200 OK]</span> <strong>$endpoint</strong>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . (strlen($response) > 500 ? "\n... (cortado)" : "") . "</pre></li>";
    } elseif ($httpCode == 401 || $httpCode == 403) {
        $encontrados++;
        echo "<li><span class='auth'>[$httpCode AUTH]</span> <strong>$endpoint</strong> (Existe, pero requiere otra clave o rol)</li>";
    } elseif ($httpCode == 405) {
        $encontrados++;
        echo "<li><span class='method'>[405 METHOD]</span> <strong>$endpoint</strong> (Existe, pero requiere POST/PUT)</li>";
    } elseif ($httpCode == 500) {
        echo "<li><span class='error'>[500 ERROR]</span> <strong>$endpoint</strong> (Falla, probablemente porque faltan parámetros)</li>";
    }
}

echo "<hr><h2>✅ Escaneo finalizado. Se encontraron $encontrados rutas activas o protegidas.</h2>";
echo "</body></html>";
