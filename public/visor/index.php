<?php
// public/visor/index.php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';

Auth::requiereSesion();
if (!Auth::puedeVerSeccion('sincronizacion')) {
    header('Location: ' . APP_URL . '/panel.php?error=sin_permiso');
    exit;
}
$titulo_pagina = 'Visor de Datos Sincronizados';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo_pagina ?> - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/libs/tabulator/tabulator.min.css">
    <style>
        .grid-visor {
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

        .header-tabla {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gris-claro);
            margin-bottom: 15px;
            padding-bottom: 10px;
        }

        .header-tabla h3 {
            border: none;
            margin: 0;
            padding: 0;
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
                    <h2>Visor de Datos Crudos (Auditoría)</h2>
                    <p>Revisa exactamente qué información se guardó en la base de datos tras sincronizar.</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div class="campo" style="min-width: 350px; margin: 0;">
                        <select id="selector-lote-visor" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--gris-borde);">
                            <option value="">Cargando lotes...</option>
                        </select>
                    </div>
                    <button class="boton boton-primario" id="btn-export-all" disabled>
                        <?= icono('descargar', 'icono-boton') ?> Exportar Todo a Excel
                    </button>
                </div>
            </div>

            <div id="contenedor-visor" style="display: none;">
                <div class="grid-visor">
                    <!-- Lote y Calidad Interna -->
                    <div class="panel-datos">
                        <h3>Información del Lote</h3>
                        <div id="info-lote"></div>
                    </div>
                    <div class="panel-datos">
                        <h3>Calidad Interna (Brix / Acidez)</h3>
                        <div id="info-interna"></div>
                    </div>
                </div>

                <div class="grid-visor" style="grid-template-columns: 1fr;">
                    <!-- Clases -->
                    <div class="panel-datos">
                        <div class="header-tabla">
                            <h3>Clases Finales Detectadas (BUENO / CAT2)</h3>
                        </div>
                        <div id="tabla-clases"></div>
                    </div>
                </div>

                <div class="grid-visor" style="grid-template-columns: 1fr;">
                    <!-- Distribuciones -->
                    <div class="panel-datos">
                        <div class="header-tabla">
                            <h3>Desglose de Medidas (Calibres, Colores, Defectos)</h3>
                        </div>
                        <div id="tabla-distribuciones"></div>
                    </div>
                </div>
            </div>
            <div id="mensaje-vacio" class="vacio" style="text-align:center; padding: 50px; color: var(--ink-soft);">
                Selecciona un lote arriba para ver su información.
            </div>
        </main>
    </div>

    <script>
        const BASE_URL = '<?= APP_URL ?>';
    </script>
    <!-- Librería para exportar a Excel -->
    <script src="<?= APP_URL ?>/assets/libs/sheetjs/xlsx.full.min.js"></script>

    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/libs/tabulator/tabulator.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/visor_datos.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>