<?php
// =====================================================================
// _debug_sync.php - Diagnóstico de la sincronización con UNITEC
// =====================================================================
// ATENCIÓN: Elimínalo después de usarlo.
// =====================================================================

require_once __DIR__ . '/../app/config/config.php';

// Solo en local
if (APP_ENV !== 'local') {
    http_response_code(403);
    die('Solo disponible en local.');
}

require_once __DIR__ . '/../app/core/auth.php';
Auth::requiereSesion();

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE SINCRONIZACIÓN ===\n\n";

// 1. Verificar que el cliente exista
$rutaCliente = __DIR__ . '/../app/core/unitec_api_client.php';
echo "1. Cliente API UNITEC: ";
if (file_exists($rutaCliente)) {
    echo "✅ Existe en $rutaCliente\n";
    require_once $rutaCliente;
} else {
    echo "❌ NO EXISTE en $rutaCliente\n";
    exit;
}

// 2. Verificar los modelos que usa el cliente
$modelosRequeridos = [
    'configuracion_api.php',
    'registro_sincronizacion.php',
    'linea.php',
    'programa.php',
    'lote.php',
    'procesamiento_lote.php',
    'registros_auditoria.php'
];
echo "\n2. Modelos en app/models/:\n";
foreach ($modelosRequeridos as $modelo) {
    $ruta = __DIR__ . '/../app/models/' . $modelo;
    echo "   - $modelo: " . (file_exists($ruta) ? "✅" : "❌ FALTA") . "\n";
}

// 3. Verificar la configuración
echo "\n3. Configuración de la API:\n";
try {
    $configModelo = new ConfiguracionApi();
    $urlBase = $configModelo->obtenerValor('url_base');
    $apiKey  = $configModelo->obtenerValor('api_key');
    echo "   - URL base: " . ($urlBase ?: '(vacía)') . "\n";
    echo "   - API Key:  " . ($apiKey ? substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) : '(vacía)') . "\n";
} catch (Exception $e) {
    echo "   ❌ Error al leer configuración: " . $e->getMessage() . "\n";
    exit;
}

// 4. Instanciar el cliente
echo "\n4. Instanciando UnitecApiClient...\n";
try {
    $cliente = new UnitecApiClient();
    echo "   ✅ Cliente creado\n";
    echo "   URL base interna: " . $cliente->obtener_url_base() . "\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit;
}

// 5. Probar la llamada a /api/Lines
echo "\n5. Llamando a /api/Lines...\n";
$inicio = microtime(true);
try {
    $lineas = $cliente->obtener_lineas();
    $ms = round((microtime(true) - $inicio) * 1000);
    echo "   ✅ Respuesta recibida en {$ms} ms\n";
    echo "   Total de líneas: " . count($lineas) . "\n\n";
    echo "   Datos:\n";
    print_r($lineas);
} catch (Exception $e) {
    $ms = round((microtime(true) - $inicio) * 1000);
    echo "   ❌ Error tras {$ms} ms: " . $e->getMessage() . "\n";
}

// 6. Últimos registros de sincronización
echo "\n6. Últimos registros de sincronización:\n";
try {
    $syncLog = new RegistroSincronizacion();
    $logs = $syncLog->listarRecientes(5);
    if (empty($logs)) {
        echo "   (Sin registros todavía)\n";
    } else {
        foreach ($logs as $log) {
            echo "   [{$log['created_at']}] {$log['metodo']} {$log['endpoint']} → {$log['codigo_respuesta']} ({$log['tiempo_ejecucion_ms']} ms)";
            if ($log['mensaje_error']) {
                echo " | ERROR: " . $log['mensaje_error'];
            }
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error al listar logs: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";