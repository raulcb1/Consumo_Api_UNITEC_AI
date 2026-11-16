<?php
// =====================================================================
// logout.php - Cierre de sesión del sistema SCPC
// =====================================================================
// Ubicación: public/logout.php
// =====================================================================

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/auth.php';

// Cerrar la sesión (destruye $_SESSION y la cookie de sesión)
Auth::cerrarSesion();

// Redirigir al login
header('Location: ' . APP_URL . '/login.php');
exit;