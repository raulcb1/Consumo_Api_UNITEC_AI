<?php
// public/lotes/listar.php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';
require_once __DIR__ . '/../../app/models/linea.php';

Auth::requiereSesion();
if (!Auth::puedeVerSeccion('lotes')) {
    header('Location: ' . APP_URL . '/panel.php?error=sin_permiso');
    exit;
}

$titulo_pagina = 'Lotes Sincronizados';
$lineaModelo = new Linea();
$lineas = $lineaModelo->listarActivas();

$puedeEditar   = Auth::tienePermiso('lotes', 'editar');
$puedeEliminar = Auth::tienePermiso('lotes', 'eliminar');
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
                    <h2>Lotes de Producción</h2>
                    <p>Historial de lotes analizados por el equipo UNITEC.</p>
                </div>
            </div>

            <div class="alerta alerta-info">
                ℹ Los lotes son creados y gestionados directamente desde el equipo UNITEC.
                Desde aquí puedes consultar sus estadísticas y enriquecer su información local.
            </div>

            <!-- Filtros -->
            <div class="panel">
                <div class="panel-encabezado">
                    <h3>Filtros</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                    <div class="campo">
                        <label for="f-linea">Línea</label>
                        <select id="f-linea">
                            <option value="">Todas las líneas</option>
                            <?php foreach ($lineas as $l): ?>
                                <option value="<?= (int) $l['id'] ?>">
                                    <?= htmlspecialchars($l['linea']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="f-buscar">Buscar</label>
                        <input type="text" id="f-buscar" placeholder="Nombre o ID de API...">
                    </div>
                    <div class="campo">
                        <label for="f-desde">Desde</label>
                        <input type="date" id="f-desde">
                    </div>
                    <div class="campo">
                        <label for="f-hasta">Hasta</label>
                        <input type="date" id="f-hasta">
                    </div>
                </div>
                <div style="margin-top: 14px; display: flex; gap: 10px;">
                    <button class="boton boton-primario" id="btn-aplicar-filtros">
                        <?= icono('buscar', 'icono-boton') ?> Aplicar
                    </button>
                    <button class="boton boton-borde" id="btn-limpiar-filtros">
                        <?= icono('cerrar', 'icono-boton') ?> Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="panel">
                <div class="panel-encabezado">
                    <h3>Resultados</h3>
                    <span id="contador-lotes" style="color: var(--ink-soft); font-size: 0.88rem;"></span>
                </div>

                <div class="tabla-wrapper">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID API</th>
                                <th>Lote</th>
                                <th>Línea</th>
                                <th>Variedad</th>
                                <th>Productor</th>
                                <th>Cajas</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Estado</th>
                                <th style="text-align: right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-lotes">
                            <tr>
                                <td colspan="10" class="vacio">Cargando lotes...</td>
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
    <script>
        const PUEDE_EDITAR = <?= $puedeEditar ? 'true' : 'false' ?>;
    </script>
    <script>
        const PUEDE_ELIMINAR = <?= $puedeEliminar ? 'true' : 'false' ?>;
    </script>
    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/lotes.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>