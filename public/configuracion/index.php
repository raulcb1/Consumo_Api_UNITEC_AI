<?php
require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';

Auth::requiereSesion();
if (!Auth::puedeVerSeccion('configuracion_api')) {
    header('Location: ' . APP_URL . '/panel.php?error=sin_permiso');
    exit;
}
$titulo_pagina = 'Gestión de Equipos';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <style>
        tr.equipo-activo {
            background-color: var(--verde-claro) !important;
        }

        .badge-activo {
            background: var(--verde);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-left: 8px;
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
                    <h2>Gestión de Equipos</h2>
                    <p>Administra las conexiones a los clasificadores UNITEC.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="boton boton-primario" id="btn-nuevo">
                        <?= icono('mas', 'icono-boton') ?> Nuevo Equipo
                    </button>
                    <a href="<?= APP_URL ?>/sincronizacion/index.php" class="boton boton-borde">
                        <?= icono('atras', 'icono-boton') ?> Volver
                    </a>
                </div>
            </div>

            <div class="panel">
                <div class="tabla-wrapper">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>URL Base</th>
                                <th>Línea por Defecto</th>
                                <th>Estado</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-equipos">
                            <tr>
                                <td colspan="5" class="vacio">Cargando equipos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL: Crear / Editar -->
    <div class="modal-fondo" id="modal-equipo">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-encabezado">
                <h3 id="modal-titulo">Configuración de Equipo</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-equipo')">×</button>
            </div>
            <form id="form-equipo" class="formulario">
                <input type="hidden" id="equipo-id">

                <div class="campo">
                    <label for="nombre">Nombre del Equipo <span class="obligatorio">*</span></label>
                    <input type="text" id="nombre" required placeholder="Ej: UNITEC Olmos 26X155">
                </div>

                <div class="campo">
                    <label for="url_base">URL Base de la API <span class="obligatorio">*</span></label>
                    <input type="text" id="url_base" required placeholder="Ej: http://192.168.1.100">
                </div>

                <div class="campo">
                    <label for="api_key">API Key <span class="obligatorio">*</span></label>
                    <input type="text" id="api_key" required placeholder="Clave de autenticación">
                    <p class="ayuda">Deje el valor enmascarado si no desea cambiarlo.</p>
                </div>

                <div class="campo-grid-2">
                    <div class="campo">
                        <label for="linea_por_defecto">Línea por Defecto</label>
                        <input type="text" id="linea_por_defecto" placeholder="Ej: 26X155">
                    </div>
                    <div class="campo">
                        <label for="tiempo_espera_segundos">Tiempo Espera (seg)</label>
                        <input type="number" id="tiempo_espera_segundos" value="20" min="5">
                    </div>
                </div>
            </form>
            <div class="modal-acciones">
                <button class="boton boton-borde" onclick="cerrarModal('modal-equipo')">Cancelar</button>
                <button class="boton boton-primario" id="btn-guardar-equipo">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= APP_URL ?>';
    </script>
    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/configuracion.js?v=<?= ASSETS_VERSION ?>"></script>
</body>

</html>