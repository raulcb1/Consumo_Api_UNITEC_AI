<?php
// =====================================================================
// sidebar.php - Barra lateral de navegación
// =====================================================================
// Ubicación: app/views/partials/sidebar.php
// Se incluye desde las vistas principales.
// =====================================================================

if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/iconos.php';

// Determinar el enlace activo según la URL actual
$rutaActual = $_SERVER['REQUEST_URI'] ?? '';
$activo = function (string $fragmento) use ($rutaActual): string {
    return strpos($rutaActual, $fragmento) !== false ? ' activo' : '';
};
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <h1>SCPC - Frusan Olmos</h1>
        <p class="slogan">Conectando Tecnología y Gestión</p>
    </div>

    <ul class="sidebar-nav">
        <?php if (Auth::puedeVerSeccion('panel')): ?>
            <li>
                <a href="<?= APP_URL ?>/panel.php" class="menu-item<?= $activo('/panel.php') ?>">
                    <?= icono('panel', 'icono-nav') ?>
                    <span>Panel</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (Auth::puedeVerSeccion('lotes')): ?>
            <li>
                <a href="<?= APP_URL ?>/lotes/listar.php" class="menu-item<?= $activo('/lotes/') ?>">
                    <?= icono('lotes', 'icono-nav') ?>
                    <span>Lotes</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (Auth::puedeVerSeccion('sincronizacion')): ?>
            <li>
                <a href="<?= APP_URL ?>/sincronizacion/index.php" class="menu-item<?= $activo('/sincronizacion/') ?>">
                    <?= icono('sincronizacion', 'icono-nav') ?>
                    <span>Sincronización</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (Auth::puedeVerSeccion('diccionario')): ?>
            <li>
                <a href="<?= APP_URL ?>/diccionario/index.php" class="menu-item<?= $activo('/diccionario/') ?>">
                    <?= icono('etiqueta', 'icono-nav') ?>
                    <span>Diccionario</span>
                    <span id="badge-faltantes" style="display:none; margin-left:auto; background: var(--rojo); color: white; border-radius: 10px; padding: 1px 7px; font-size: 0.7rem; font-weight: 600;"></span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (Auth::puedeVerSeccion('reportes')): ?>
            <li>
                <a href="<?= APP_URL ?>/reportes/index.php" class="menu-item<?= $activo('/reportes/') ?>">
                    <?= icono('reportes', 'icono-nav') ?>
                    <span>Reportes</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (Auth::puedeVerSeccion('usuarios')): ?>
            <li>
                <a href="<?= APP_URL ?>/usuarios/listar.php" class="menu-item<?= $activo('/usuarios/') ?>">
                    <?= icono('usuarios', 'icono-nav') ?>
                    <span>Usuarios</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (Auth::puedeVerSeccion('configuracion_api')): ?>
            <li>
                <a href="<?= APP_URL ?>/configuracion/index.php" class="menu-item<?= $activo('/configuracion/') ?>">
                    <?= icono('configuracion', 'icono-nav') ?>
                    <span>Configuración</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <div class="version">SCPC v1.0</div>
        <a href="<?= APP_URL ?>/logout.php" class="menu-item-salir">
            <?= icono('salir', 'icono-nav') ?>
            <span>Cerrar sesión</span>
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const badge = document.getElementById('badge-faltantes');
            if (!badge || typeof Api === 'undefined') return;
            const r = await Api.peticion('/api/clases_diccionario.php?accion=listar_faltantes');
            if (r.ok && r.data?.success && Array.isArray(r.data.data) && r.data.data.length > 0) {
                badge.textContent = r.data.data.length;
                badge.style.display = 'inline-block';
            }
        });
    </script>
</aside>
<div class="sidebar-overlay"></div>