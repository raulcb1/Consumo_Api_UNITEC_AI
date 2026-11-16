<?php
// =====================================================================
// index.php - Panel de sincronización con la API UNITEC
// =====================================================================
// Ubicación: public/sincronizacion/index.php
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';
require_once __DIR__ . '/../../app/models/linea.php';
require_once __DIR__ . '/../../app/models/configuracion_api.php';

Auth::requiereSesion();

if (!Auth::puedeVerSeccion('sincronizacion')) {
    header('Location: ' . APP_URL . '/panel.php?error=sin_permiso');
    exit;
}

$titulo_pagina = 'Sincronización';

// Cargar líneas locales para el selector
$lineaModelo = new Linea();
$lineas = $lineaModelo->listarActivas();

// Leer configuración de la API
$configModelo = new ConfiguracionApi();
$urlBase = $configModelo->obtenerValor('url_base') ?? '(no configurada)';
$apiKey  = $configModelo->obtenerValor('api_key') ?? '';
$apiKeyMostrar = $apiKey ? substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) : '(no configurada)';

$puedeEjecutar = Auth::tienePermiso('sincronizacion', 'ejecutar');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
</head>

<body>
    <div class="layout">
        <?php require_once __DIR__ . '/../../app/views/partials/sidebar.php'; ?>
        <?php require_once __DIR__ . '/../../app/views/partials/topbar.php'; ?>

        <main class="contenido">

            <div class="contenido-encabezado">
                <div>
                    <h2>Sincronización con UNITEC</h2>
                    <p>Importa líneas, programas y lotes desde la API del fabricante.</p>
                </div>
            </div>

            <!-- Estado de la configuración -->
            <div class="panel">
                <div class="panel-encabezado">
                    <h3>Configuración actual de la API</h3>
                    <?php if (Auth::puedeVerSeccion('configuracion_api')): ?>
                        <a href="<?= APP_URL ?>/configuracion/index.php" class="boton boton-borde boton-sm">
                            Editar
                        </a>
                    <?php endif; ?>
                </div>
                <div class="tabla-wrapper">
                    <table class="tabla">
                        <tbody>
                            <tr>
                                <td style="width: 30%;"><strong>URL base</strong></td>
                                <td><code><?= htmlspecialchars($urlBase) ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>API Key</strong></td>
                                <td><code><?= htmlspecialchars($apiKeyMostrar) ?></code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!$puedeEjecutar): ?>
                <div class="alerta alerta-info">
                    No tienes permisos para ejecutar sincronizaciones. Solo puedes consultar el historial.
                </div>
            <?php else: ?>

                <!-- Acciones de sincronización -->
                <div class="panel">
                    <div class="panel-encabezado">
                        <h3>Acciones</h3>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                        <button class="boton boton-primario" id="btn-sinc-lineas">
                            <?= icono('sincronizacion', 'icono-boton') ?>
                            <span>Sincronizar líneas</span>
                        </button>
                        <button class="boton boton-info" id="btn-sinc-estadisticas">
                            <?= icono('estadisticas', 'icono-boton') ?>
                            <span>Sincronizar estadísticas</span>
                        </button>
                        <button class="boton boton-info" id="btn-sinc-todo">
                            <?= icono('refrescar', 'icono-boton') ?>
                            <span>Sincronizar todo</span>
                        </button>
                        <div class="campo">
                            <p class="ayuda">
                                <strong>Sincronizar todo</strong>: si hay una línea seleccionada, sincroniza solo esa línea (programas y lotes).
                                Si no hay ninguna línea seleccionada, sincroniza <em>todo el sistema</em>.
                            </p>
                        </div>
                    </div>



                    <div class="campo" style="max-width: 500px;">
                        <label for="selector-linea">Línea para sincronizar programas y lotes</label>
                        <select id="selector-linea">
                            <!-- Opción estática inicial (no la toca el JS) -->
                            <option value="" data-estatica="1">— Selecciona una línea —</option>

                            <!-- Aquí el JS insertará las líneas dinámicamente -->

                            <!-- Opción estática final (no la toca el JS) -->
                            <?php if (!empty($lineas)): ?>
                                <option value="todas" data-estatica="1" data-todas="1"
                                    style="font-weight: 600; color: var(--verde);">
                                    — Sincronizar todas las líneas —
                                </option>
                            <?php endif; ?>
                        </select>
                        <p class="ayuda">
                            Selecciona una línea concreta, o <strong>"Sincronizar todas las líneas"</strong> para procesar el sistema completo.
                        </p>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 16px;">
                        <button class="boton boton-secundario" id="btn-sinc-programas" disabled>
                            <?= icono('programas', 'icono-boton') ?>
                            <span>Sincronizar programas</span>
                        </button>
                        <button class="boton boton-secundario" id="btn-sinc-lotes" disabled>
                            <?= icono('lotes', 'icono-boton') ?>
                            <span>Sincronizar lotes</span>
                        </button>
                    </div>
                </div>

            <?php endif; ?>

            <!-- Estado en vivo -->
            <div class="panel" id="panel-estado" style="display: none;">
                <div class="panel-encabezado">
                    <h3>Estado de la operación</h3>
                </div>
                <div id="estado-contenido"></div>
            </div>

            <!-- Historial de sincronización -->
            <div class="panel">
                <div class="panel-encabezado">
                    <h3>Últimas sincronizaciones</h3>
                    <button class="boton boton-borde boton-sm" id="btn-refrescar-logs">
                        <?= icono('refrescar', 'icono-boton') ?>
                        <span>Refrescar</span>
                    </button>
                </div>

                <div class="tabla-wrapper">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Método</th>
                                <th>Endpoint</th>
                                <th>Estado</th>
                                <th>Tiempo</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-logs">
                            <tr>
                                <td colspan="5" class="vacio">Cargando registros...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const BASE_URL = '<?= APP_URL ?>';
    </script>
    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/sincronizacion.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>