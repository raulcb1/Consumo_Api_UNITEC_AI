<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';

Auth::requiereSesion();
if (!Auth::puedeVerSeccion('reportes')) {
    header('Location: ' . APP_URL . '/panel.php?error=sin_permiso');
    exit;
}
$codigo = $_GET['codigo'] ?? '';
if (!$codigo) {
    header('Location: ' . APP_URL . '/reportes/index.php');
    exit;
}
$titulo_pagina = 'Reporte';
$puedeExportar = Auth::tienePermiso('reportes', 'exportar');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - SCPC</title>

    <!-- CSS general -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">

    <!-- SheetJS (Debe ir antes que Tabulator) -->
    <script src="<?= APP_URL ?>/assets/libs/sheetjs/xlsx.full.min.js"></script>

    <!-- Tabulator CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/libs/tabulator/tabulator.min.css">

    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/libs/apexcharts/apexcharts.css">

    <style>
        /* Ajustes para integrar Tabulator al diseño existente */
        .tabulator {
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            font-family: var(--fuente);
        }

        .tabulator .tabulator-header {
            background-color: var(--gris-claro);
            border-bottom: 1px solid var(--gris-borde);
        }

        .tabulator .tabulator-header .tabulator-col {
            font-weight: 600;
            color: var(--ink-soft);
        }

        .tabulator .tabulator-row:hover {
            background-color: var(--gris-claro);
        }

        /* Contenedor del gráfico */
        #grafico-reporte {
            min-height: 300px;
            margin-bottom: 20px;
            background: var(--blanco);
            padding: 16px;
            border-radius: 8px;
            border: 1px solid var(--gris-borde);
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
                    <h2 id="titulo-reporte">Cargando...</h2>
                    <p id="descripcion-reporte"></p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="boton boton-primario" id="btn-ejecutar">
                        <?= icono('refrescar', 'icono-boton') ?>
                        <span>Ejecutar / Refrescar</span>
                    </button>
                    <a href="<?= APP_URL ?>/reportes/index.php" class="boton boton-borde">
                        <?= icono('atras', 'icono-boton') ?>
                        <span>Volver</span>
                    </a>
                </div>
            </div>

            <!-- Filtros -->
            <div class="panel">
                <div class="panel-encabezado">
                    <h3>Filtros</h3>
                    <button class="boton boton-borde boton-sm" id="btn-limpiar-filtros">Limpiar</button>
                </div>
                <div id="filtros-contenedor" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;"></div>
            </div>

            <!-- Gráfico (ApexCharts) -->
            <div id="contenedor-grafico" style="display: none;">
                <div id="grafico-reporte"></div>
            </div>

            <!-- Tabla (Tabulator) -->
            <div class="panel">
                <div class="panel-encabezado">
                    <h3>Resultados</h3>
                    <span id="meta-resultado" style="color: var(--ink-soft); font-size: 0.85rem;"></span>
                </div>

                <div id="tabla-tabulator"></div>
            </div>
        </main>
    </div>

    <!-- MODAL: Evolución temporal del lote -->
    <div class="modal-fondo" id="modal-evolucion">
        <div class="modal" style="max-width: 900px;">
            <div class="modal-encabezado">
                <h3 id="titulo-evolucion">Evolución del Lote</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-evolucion')">×</button>
            </div>
            <div class="modal-body">
                <div id="grafico-evolucion" style="min-height: 400px;"></div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= APP_URL ?>';
    </script>
    <script>
        const REPORTE_CODIGO = '<?= htmlspecialchars($codigo) ?>';
    </script>
    <script>
        const PUEDE_EXPORTAR = <?= $puedeExportar ? 'true' : 'false' ?>;
    </script>

    <!-- JS general y dependencias -->
    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>

    <!-- Tabulator JS -->
    <script src="<?= APP_URL ?>/assets/libs/tabulator/tabulator.min.js"></script>

    <!-- ApexCharts JS -->
    <script src="<?= APP_URL ?>/assets/libs/apexcharts/apexcharts.min.js"></script>

    <!-- JS del reporte -->
    <script src="<?= APP_URL ?>/assets/js/reportes_ver.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>