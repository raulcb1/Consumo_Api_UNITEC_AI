<?php
// public/panel.php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/iconos.php';

Auth::requiereSesion();
if (!Auth::puedeVerSeccion('panel')) {
    header('Location: ' . APP_URL . '/logout.php');
    exit;
}
$usuario = Auth::usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Análisis - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/libs/apexcharts/apexcharts.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/libs/tabulator/tabulator.min.css">
    <style>
        /* Corrección de Grid para que no se oculten las columnas de la derecha */
        .grid-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .panel-grafico {
            background: var(--blanco);
            border-radius: 8px;
            padding: 15px;
            box-shadow: var(--sombra-suave);
            min-width: 0;
            /* Evita que el contenido empuje y rompa el grid */
            overflow: hidden;
        }

        .panel-grafico h3 {
            font-size: 1rem;
            color: var(--ink-soft);
            margin-bottom: 15px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .filtros-panel {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
            background: var(--blanco);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: var(--sombra-suave);
        }

        /* Forzar altura mínima para que los gráficos no desaparezcan */
        div[id^="chart-"] {
            min-height: 320px;
            width: 100%;
        }

        /* Cabecera PDF */
        #pdf-header {
            display: none;
        }

        @media print {
            body {
                background: white;
            }

            .sidebar,
            .topbar,
            .filtros-panel,
            .btn-export,
            .btn-accion,
            .btn-debug {
                display: none !important;
            }

            .layout {
                display: block;
            }

            .contenido {
                padding: 0;
            }

            #pdf-header {
                display: block !important;
                margin-bottom: 30px;
            }

            .grid-dashboard {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="layout">
        <?php require_once __DIR__ . '/../app/views/partials/sidebar.php'; ?>
        <?php require_once __DIR__ . '/../app/views/partials/topbar.php'; ?>

        <main class="contenido">
            <!-- CABECERA SOLO PARA PDF -->
            <div id="pdf-header">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--verde); padding-bottom: 15px;">
                    <div>
                        <h1 style="margin: 0; font-size: 1.6rem; color: var(--verde);">FRUSAN OLMOS</h1>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--ink-soft);">Sistema de Control de Producción y Calidad</p>
                    </div>
                    <div style="text-align: right;">
                        <h2 style="margin: 0; font-size: 1.3rem; color: var(--ink);">Resumen Ejecutivo de Calidad</h2>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--ink-soft);">Generado: <span id="pdf-fecha"></span></p>
                    </div>
                </div>
            </div>

            <div class="contenido-encabezado">
                <div>
                    <h2>Panel de Análisis</h2>
                    <p>Resumen ejecutivo y análisis de calidad de los lotes procesados.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="boton boton-primario btn-export" id="btn-export-pdf">
                        <?= icono('descargar', 'icono-boton') ?> Exportar a PDF
                    </button>
                    <a href="<?= APP_URL ?>/lotes/listar.php" class="boton boton-info btn-accion">
                        <?= icono('lotes', 'icono-boton') ?> Revisar por Lote
                    </a>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros-panel">
                <div class="campo" style="min-width: 150px;">
                    <label>Desde</label>
                    <input type="date" id="f-inicio" style="padding: 8px; border-radius: 6px; border: 1px solid var(--gris-borde);">
                </div>
                <div class="campo" style="min-width: 150px;">
                    <label>Hasta</label>
                    <input type="date" id="f-fin" style="padding: 8px; border-radius: 6px; border: 1px solid var(--gris-borde);">
                </div>
                <div class="campo" style="min-width: 200px;">
                    <label>Programa</label>
                    <select id="f-programa" style="padding: 8px; border-radius: 6px; border: 1px solid var(--gris-borde);">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div style="display: flex; gap: 5px;">
                    <button class="boton boton-borde boton-sm btn-fecha" data-rango="mes">Último mes</button>
                    <button class="boton boton-borde boton-sm btn-fecha" data-rango="3meses">3 Meses</button>
                    <button class="boton boton-borde boton-sm btn-fecha" data-rango="año">Este Año</button>
                    <button class="boton boton-primario boton-sm" id="btn-aplicar">Aplicar</button>
                </div>
            </div>

            <!-- CONTENEDOR DEL PDF -->
            <div id="contenedor-pdf">
                <!-- Resumen Ejecutivo -->
                <div class="tarjetas" id="kpis-dashboard">
                    <div class="tarjeta">
                        <div class="etiqueta">Lotes Analizados</div>
                        <div class="valor" id="kpi-lotes">—</div>
                    </div>
                    <div class="tarjeta azul">
                        <div class="etiqueta">Muestras (Cajas)</div>
                        <div class="valor" id="kpi-muestras">—</div>
                    </div>
                    <div class="tarjeta">
                        <div class="etiqueta">% Exportable (BUENO)</div>
                        <div class="valor" id="kpi-bueno" style="color: var(--verde);">—</div>
                    </div>
                    <div class="tarjeta naranja">
                        <div class="etiqueta">% Desecho (CAT2)</div>
                        <div class="valor" id="kpi-desecho" style="color: var(--naranja);">—</div>
                    </div>
                </div>

                <!-- Fila 1: Calibres y Calidad Interna -->
                <div class="grid-dashboard">
                    <div class="panel-grafico">
                        <div style="display:flex; justify-content:space-between;">
                            <h3>Análisis de Calibres</h3>
                            <button class="boton boton-borde boton-sm btn-debug" data-chart="calibres" style="display:none;">🐞 Debug</button>
                        </div>
                        <div id="chart-calibres"></div>
                    </div>
                    <div class="panel-grafico">
                        <div style="display:flex; justify-content:space-between;">
                            <h3>Calidad Interna Promedio</h3>
                            <button class="boton boton-borde boton-sm btn-debug" data-chart="interna" style="display:none;">🐞 Debug</button>
                        </div>
                        <div class="tabla-wrapper">
                            <table class="tabla" id="tabla-interna">
                                <thead>
                                    <tr>
                                        <th>Métrica</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="vacio">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Fila 2: Defectos y Colores -->
                <div class="grid-dashboard">
                    <div class="panel-grafico">
                        <div style="display:flex; justify-content:space-between;">
                            <h3>Desglose de Defectos (CAT2)</h3>
                            <button class="boton boton-borde boton-sm btn-debug" data-chart="defectos" style="display:none;">🐞 Debug</button>
                        </div>
                        <div id="chart-defectos"></div>
                    </div>
                    <div class="panel-grafico">
                        <div style="display:flex; justify-content:space-between;">
                            <h3>Calidad Visual (Color)</h3>
                            <button class="boton boton-borde boton-sm btn-debug" data-chart="colores" style="display:none;">🐞 Debug</button>
                        </div>
                        <div id="chart-colores"></div>
                    </div>
                </div>
            </div>

            <!-- TABLA DE DATOS CRUDOS -->
            <div class="panel" style="margin-top: 20px;">
                <div class="panel-encabezado">
                    <h3>Datos Crudos (Lotes Filtrados)</h3>
                    <div style="display: flex; gap: 8px;">
                        <button class="boton boton-borde btn-export" id="btn-export-json">
                            <?= icono('descargar', 'icono-boton') ?> Exportar JSON
                        </button>
                        <button class="boton boton-secundario btn-export" id="btn-export-excel">
                            <?= icono('descargar', 'icono-boton') ?> Exportar a Excel
                        </button>
                        <button class="boton boton-info btn-export" id="btn-export-html">
                            <?= icono('descargar', 'icono-boton') ?> Exportar Dashboard HTML
                        </button>
                    </div>
                </div>
                <div id="tabla-datos-crudos"></div>
            </div>

        </main>
    </div>

    <!-- MODAL DE DEBUG -->
    <div class="modal-fondo" id="modal-debug">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-encabezado">
                <h3 id="debug-titulo">Datos del Gráfico</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-debug')">×</button>
            </div>
            <pre id="debug-contenido" style="max-height: 500px; overflow-y: auto; background: #1e293b; color: #00e396; padding: 15px; border-radius: 8px; font-size: 12px; white-space: pre-wrap;"></pre>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= APP_URL ?>';
    </script>
    <script src="<?= APP_URL ?>/assets/libs/html2pdf/html2pdf.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/libs/sheetjs/xlsx.full.min.js"></script>
    <script src="<?= APP_URL ?>/assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="<?= APP_URL ?>/assets/libs/tabulator/tabulator.min.js"></script>

    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/panel.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>