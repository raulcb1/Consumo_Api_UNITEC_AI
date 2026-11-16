<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';
Auth::requiereSesion();
$titulo_pagina = 'Explorador Crossfilter';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo_pagina ?> - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/libs/apexcharts/apexcharts.css">
    <style>
        .grid-crossfilter {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .panel-grafico {
            background: var(--blanco);
            border-radius: 8px;
            padding: 15px;
            box-shadow: var(--shadow-sm);
        }

        .panel-grafico h4 {
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--ink-soft);
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div class="layout">
        <?php require_once __DIR__ . '/../../app/views/partials/sidebar.php'; ?>
        <?php require_once __DIR__ . '/../../app/views/partials/topbar.php'; ?>

        <main class="contenido">
            <div class="contenido-encabezado">
                <div>
                    <h2>Explorador Crossfilter (Jerárquico)</h2>
                    <p>Haz clic en cualquier gráfico para filtrar los demás. Usa el botón "Reset" para limpiar los filtros.</p>
                </div>
                <button class="boton boton-primario" id="btn-reset">Resetear Filtros</button>
            </div>

            <div class="grid-crossfilter">
                <div class="panel-grafico">
                    <h4>1. Kilos por Lote</h4>
                    <div id="chart-lotes"></div>
                </div>
                <div class="panel-grafico">
                    <h4>2. Distribución por Programa</h4>
                    <div id="chart-programas"></div>
                </div>
            </div>

            <div class="grid-crossfilter">
                <div class="panel-grafico">
                    <h4>3. Distribución por Tipo de Clase</h4>
                    <div id="chart-tipos"></div>
                </div>
                <div class="panel-grafico">
                    <h4>4. Evolución Temporal (Clases)</h4>
                    <div id="chart-evolucion"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const BASE_URL = '<?= APP_URL ?>';
    </script>
    <script src="<?= APP_URL ?>/assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/analitica.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>