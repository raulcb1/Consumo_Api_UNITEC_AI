<?php
// =====================================================================
// topbar.php - Barra superior con título, usuario y botón de menú
// =====================================================================
// Ubicación: app/views/partials/topbar.php
// Variables esperadas antes de incluir:
//   $titulo_pagina (string) - Título que se muestra en el topbar
// =====================================================================

if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/iconos.php';

$usuarioActual = Auth::usuarioActual();
$nombreMostrar = $usuarioActual['empleado_nombre'] ?? $usuarioActual['usuario'] ?? 'Usuario';
$rolMostrar    = $usuarioActual['rol'] ?? '';
?>
<header class="topbar">
    <button class="boton-menu" aria-label="Abrir menú">☰</button>

    <div class="topbar-titulo">
        <?= htmlspecialchars($titulo_pagina ?? 'Panel') ?>
    </div>

    <div class="topbar-usuario">
        <div class="avatar" title="<?= htmlspecialchars($nombreMostrar) ?>">
            <?= icono('usuario', 'icono-avatar') ?>
        </div>
        <div class="datos">
            <div class="nombre"><?= htmlspecialchars($nombreMostrar) ?></div>
            <div class="rol"><?= htmlspecialchars($rolMostrar) ?></div>
        </div>
    </div>
</header>