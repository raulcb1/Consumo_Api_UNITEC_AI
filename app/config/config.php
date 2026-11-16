<?php
// =====================================================================
// Configuracion general del sistema
// =====================================================================
// Cuando el sistema se suba a un dominio real, cambiar esta linea a
// 'produccion' y completar los datos del bloque correspondiente abajo.
define('APP_ENV', 'local'); // 'local' o 'produccion'

// ---------------------------------------------------------------------
// Cargar secretos (credenciales de BD, correo, etc.)
// ---------------------------------------------------------------------
require_once __DIR__ . '/secrets.php';

// ---------------------------------------------------------------------
// Configuración de la base de datos (solo charset, que no es secreto)
// ---------------------------------------------------------------------
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------
// URL base y datos generales de la app (con detección automática de HTTPS)
// ---------------------------------------------------------------------
define('APP_NOMBRE', 'Sistema de Control de Producción y Calidad - Frusan Olmos');
define('SLOGAN', 'Conectando Tecnología y Gestión');

if (APP_ENV === 'local') {
    // Detectar protocolo (HTTP o HTTPS)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', "{$protocol}://{$host}/SCPCFrusan/public");
} else {
    define('APP_URL', 'https://tu-dominio.com');
}

// ---------------------------------------------------------------------
// Versionado de assets (para evitar caché del navegador)
// ---------------------------------------------------------------------
// Calcula una versión única basada en el filemtime MÁS RECIENTE de
// TODOS los assets (CSS + JS). Así, cada vez que se edita cualquiera,
// la versión cambia y los navegadores descargan la nueva.
// ---------------------------------------------------------------------
// ---------------------------------------------------------------------
// Versionado de assets (auto-detectado)
// ---------------------------------------------------------------------
$timestamps = [time()];

// CSS
$cssDir = __DIR__ . '/../../public/assets/css';
if (is_dir($cssDir)) {
    foreach (glob($cssDir . '/*.css') as $archivo) {
        $timestamps[] = filemtime($archivo);
    }
}

// JS
$jsDir = __DIR__ . '/../../public/assets/js';
if (is_dir($jsDir)) {
    foreach (glob($jsDir . '/*.js') as $archivo) {
        $timestamps[] = filemtime($archivo);
    }
}

define('ASSETS_VERSION', max($timestamps));

// ---------------------------------------------------------------------
// Carga de permisos y secciones del sistema
// ---------------------------------------------------------------------
$permisos = require_once __DIR__ . '/permisos.php';
define('PERMISOS', $permisos);

// ---------------------------------------------------------------------
// Manejo de errores según entorno
// ---------------------------------------------------------------------
if (APP_ENV === 'local') {
    // En local: mostrar todo, ayuda a depurar
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // En produccion: ocultar errores al usuario y guardarlos en un log
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../logs/errores_php.log');
    error_reporting(E_ALL);
}

// ---------------------------------------------------------------------
// Configuración de sesiones (login)
// ---------------------------------------------------------------------
ini_set('session.cookie_httponly', 1);
if (APP_ENV !== 'local') {
    // Solo forzar cookies "secure" cuando ya haya HTTPS real (produccion)
    ini_set('session.cookie_secure', 1);
}

// Iniciar sesión automáticamente para toda la aplicación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
