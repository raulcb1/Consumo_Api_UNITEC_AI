<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';

Auth::requiereSesion();
if (!Auth::puedeVerSeccion('reportes')) {
    header('Location: ' . APP_URL . '/panel.php?error=sin_permiso');
    exit;
}
$titulo_pagina = 'Reportes';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <style>
        .grid-reportes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .tarjeta-reporte {
            background: var(--blanco);
            border-radius: var(--radio);
            padding: 20px;
            box-shadow: var(--sombra-suave);
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            text-decoration: none;
            color: var(--ink);
            border-left: 4px solid var(--verde);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .tarjeta-reporte:hover {
            transform: translateY(-2px);
            box-shadow: var(--sombra-media);
            text-decoration: none;
        }

        .tarjeta-reporte .icono {
            width: 32px;
            height: 32px;
            color: var(--verde);
        }

        .tarjeta-reporte .titulo {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--ink);
        }

        .tarjeta-reporte .descripcion {
            font-size: 0.85rem;
            color: var(--ink-soft);
            line-height: 1.4;
        }

        .tarjeta-reporte .categoria {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--verde);
            font-weight: 600;
            margin-top: auto;
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
                    <h2>Reportes</h2>
                    <p>Consulta, filtra y exporta la información del sistema.</p>
                </div>
            </div>

            <div class="grid-reportes" id="grid-reportes">
                <p style="grid-column: 1/-1; color: var(--ink-soft);">Cargando reportes...</p>
            </div>
        </main>
    </div>

    <script>
        const BASE_URL = '<?= APP_URL ?>';
    </script>
    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/reportes.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>