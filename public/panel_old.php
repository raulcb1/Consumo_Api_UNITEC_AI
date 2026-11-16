<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/iconos.php';

Auth::requiereSesion();
if (!Auth::puedeVerSeccion('panel')) {
    header('Location: ' . APP_URL . '/logout.php');
    exit;
}

$titulo_pagina = 'Panel de Control';
$usuario = Auth::usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/libs/apexcharts/apexcharts.css">
    <style>
        .grid-dashboard { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .panel-grafico { background: var(--blanco); border-radius: 8px; padding: 15px; box-shadow: var(--sombra-suave); }
        .panel-grafico h3 { font-size: 1rem; color: var(--ink-soft); margin-bottom: 15px; text-transform: uppercase; font-weight: 600; }
        .kpi-card { cursor: pointer; transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-2px); }
        @media (max-width: 900px) { .grid-dashboard { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="layout">
        <?php require_once __DIR__ . '/../app/views/partials/sidebar.php'; ?>
        <?php require_once __DIR__ . '/../app/views/partials/topbar.php'; ?>

        <main class="contenido">
            <div class="contenido-encabezado">
                <div>
                    <h2>Panel Principal</h2>
                    <p>Bienvenido, <?= htmlspecialchars($usuario['empleado_nombre'] ?? $usuario['usuario'] ?? '') ?>. Haz clic en los gráficos para filtrar la información.</p>
                </div>
                <button class="boton boton-primario" id="btn-reset-filtros">
                    <?= icono('refrescar', 'icono-boton') ?>
                    <span>Limpiar Filtros</span>
                </button>
            </div>

            <!-- Tarjetas KPI (Reaccionan a los filtros) -->
            <div class="tarjetas">
                <div class="tarjeta kpi-card" id="kpi-kg-card">
                    <div class="etiqueta">Total Kg Clasificados</div>
                    <div class="valor" id="kpi-kg">—</div>
                </div>
                <div class="tarjeta azul kpi-card" id="kpi-lotes-card">
                    <div class="etiqueta">Lotes Procesados</div>
                    <div class="valor" id="kpi-lotes">—</div>
                </div>
                <div class="tarjeta naranja kpi-card">
                    <div class="etiqueta">Clases Detectadas</div>
                    <div class="valor" id="kpi-clases">—</div>
                </div>
            </div>

            <!-- Fila 1: Lotes y Programas -->
            <div class="grid-dashboard">
                <div class="panel-grafico">
                    <h3>1. Kilos por Lote</h3>
                    <div id="chart-lotes"></div>
                </div>
                <div class="panel-grafico">
                    <h3>2. Distribución por Programa</h3>
                    <div id="chart-programas"></div>
                </div>
            </div>

            <!-- Fila 2: Tipos y Evolución -->
            <div class="grid-dashboard">
                <div class="panel-grafico">
                    <h3>3. Distribución por Tipo de Clase</h3>
                    <div id="chart-tipos"></div>
                </div>
                <div class="panel-grafico">
                    <h3>4. Evolución Temporal (Clases)</h3>
                    <div id="chart-evolucion"></div>
                </div>
            </div>

        </main>
    </div>

    <script>const BASE_URL = '<?= APP_URL ?>';</script>
    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/panel.js?v=<?= ASSETS_VERSION ?>"></script>
</body>
</html>