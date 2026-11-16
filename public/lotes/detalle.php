<?php
// public/lotes/detalle.php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';

Auth::requiereSesion();
if (!Auth::puedeVerSeccion('lotes')) {
    header('Location: ' . APP_URL . '/panel.php?error=sin_permiso');
    exit;
}

$loteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($loteId <= 0) {
    header('Location: ' . APP_URL . '/lotes/listar.php');
    exit;
}

$titulo_pagina = 'Detalle del Lote';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <style>
        .grid-detalle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .panel-datos {
            background: var(--blanco);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--sombra-suave);
        }

        .panel-datos h3 {
            font-size: 1rem;
            color: var(--ink-soft);
            margin-bottom: 15px;
            border-bottom: 1px solid var(--gris-claro);
            padding-bottom: 10px;
        }

        .dato-fila {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed var(--gris-borde);
        }

        .dato-fila strong {
            color: var(--ink-soft);
        }

        /* Estilos para los sub-paneles de Medidas y Defectos */
        .grid-medidas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        .cuadro-medida {
            background: var(--gris-claro);
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            padding: 12px 15px;
        }

        .cuadro-medida h4 {
            font-size: 0.9rem;
            color: var(--verde);
            margin-bottom: 10px;
            border-bottom: 1px solid var(--gris-borde);
            padding-bottom: 5px;
        }

        .tabla-mini {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .tabla-mini th {
            text-align: left;
            color: var(--ink-soft);
            padding-bottom: 5px;
            font-weight: 600;
        }

        .tabla-mini td {
            padding: 6px 0;
            border-bottom: 1px dashed var(--gris-borde);
        }

        @media print {

            /* Ocultar elementos de la interfaz */
            .sidebar,
            .topbar,
            .sidebar-overlay,
            .contenido-encabezado,
            #btn-exportar-pdf,
            #btn-exportar-excel {
                display: none !important;
            }

            /* Mostrar la cabecera del PDF */
            #pdf-header {
                display: block !important;
            }

            /* Ajustar el layout */
            .layout {
                display: block !important;
            }

            .contenido {
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Imprimir fondos y colores */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Evitar cortes feos */
            .panel-datos,
            .cuadro-medida {
                page-break-inside: avoid;
            }
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
                    <h2 id="titulo-lote">Cargando...</h2>
                    <p id="subtitulo-lote"></p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="boton boton-secundario" id="btn-exportar-pdf">
                        <?= icono('descargar', 'icono-boton') ?>
                        <span>Exportar a PDF</span>
                    </button>

                    <button class="boton boton-primario" id="btn-exportar-excel">
                        <?= icono('descargar', 'icono-boton') ?>
                        <span>Generar Reporte Excel</span>
                    </button>

                    <a href="<?= APP_URL ?>/lotes/listar.php" class="boton boton-borde">
                        <?= icono('atras', 'icono-boton') ?> Volver al listado
                    </a>
                </div>
            </div>

            <!-- CABECERA SOLO PARA EL PDF -->
            <div id="pdf-header" class="solo-para-pdf" style="display: none; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--verde); padding-bottom: 15px;">
                    <!-- Lado Izquierdo: Logo y Empresa -->
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <!-- ruta de tu logo -->
                        <img src="<?= APP_URL ?>/assets/img/logo_frusan.png" style="height: 60px;" alt="Logo">
                        <div>
                            <h1 style="margin: 0; font-size: 1.6rem; color: var(--verde);">FRUSAN OLMOS</h1>
                            <p style="margin: 0; font-size: 0.85rem; color: var(--ink-soft);">Sistema de Control de Producción y Calidad</p>
                        </div>
                    </div>

                    <!-- Lado Derecho: Título del Reporte y Fecha -->
                    <div style="text-align: right;">
                        <h2 style="margin: 0; font-size: 1.3rem; color: var(--ink);">Reporte de Calidad</h2>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--ink-soft);">
                            Generado el: <span id="pdf-fecha-generacion"></span>
                        </p>
                    </div>
                </div>

                <!-- Información del Lote en la Cabecera -->
                <div style="margin-top: 15px; display: flex; justify-content: space-between; background: var(--gris-claro); padding: 10px 15px; border-radius: 8px;">
                    <div><strong>Lote:</strong> <span id="pdf-lote-nombre">Cargando...</span></div>
                    <div><strong>ID API:</strong> <span id="pdf-lote-id">—</span></div>
                    <div><strong>Variedad:</strong> <span id="pdf-lote-variedad">—</span></div>
                </div>
            </div>


            <div id="contenedor-detalle" style="display: none;">
                <div class="grid-detalle">
                    <!-- Datos Generales -->
                    <div class="panel-datos">
                        <h3>Datos Generales (UNITEC)</h3>
                        <div id="info-lote"></div>
                    </div>
                    <!-- Calidad Interna -->
                    <div class="panel-datos">
                        <h3>Calidad Interna (Brix / Acidez)</h3>
                        <div id="info-interna"></div>
                    </div>
                </div>

                <div class="grid-detalle" style="grid-template-columns: 1fr;">
                    <!-- Clases Finales -->
                    <div class="panel-datos">
                        <h3>Clases Finales Detectadas</h3>
                        <div class="tabla-wrapper">
                            <table class="tabla" id="tabla-clases">
                                <thead>
                                    <tr>
                                        <th>Clase</th>
                                        <th>Porcentaje (%)</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="grid-detalle" style="grid-template-columns: 1fr;">
                    <!-- Medidas y Defectos (Sub-paneles) -->
                    <div class="panel-datos">
                        <h3>Medidas y Defectos</h3>

                        <div class="grid-medidas">
                            <!-- Cuadro 1: Calibres -->
                            <div class="cuadro-medida">
                                <h4>CALIBRES</h4>
                                <table class="tabla-mini" id="tabla-calibres">
                                    <thead>
                                        <tr>
                                            <th>Rango</th>
                                            <th style="text-align:right;">Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <!-- Cuadro 2: Defectos -->
                            <div class="cuadro-medida">
                                <h4>DEFECTOS (CAT2)</h4>
                                <table class="tabla-mini" id="tabla-defectos">
                                    <thead>
                                        <tr>
                                            <th>Regla</th>
                                            <th style="text-align:right;">Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <!-- Cuadro 3: Calidad Global -->
                            <div class="cuadro-medida">
                                <h4>CALIDAD GLOBAL</h4>
                                <table class="tabla-mini" id="tabla-calidad">
                                    <thead>
                                        <tr>
                                            <th>Rango</th>
                                            <th style="text-align:right;">Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <!-- Cuadro 4: Colores -->
                            <div class="cuadro-medida">
                                <h4>COLOR (VISUAL)</h4>
                                <table class="tabla-mini" id="tabla-colores">
                                    <thead>
                                        <tr>
                                            <th>Rango</th>
                                            <th style="text-align:right;">Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div id="mensaje-vacio" class="vacio" style="text-align:center; padding: 50px; color: var(--ink-soft);">
                Cargando información del lote...
            </div>
        </main>
    </div>

    <script>
        const BASE_URL = '<?= APP_URL ?>';
    </script>
    <script>
        const LOTE_ID = <?= (int)$loteId ?>;
    </script>
    <script src="<?= APP_URL ?>/assets/libs/html2pdf/html2pdf.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/lote_detalle.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>