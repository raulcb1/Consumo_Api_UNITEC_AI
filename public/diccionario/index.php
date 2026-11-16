<?php
// =====================================================================
// index.php - Diccionario de clases
// =====================================================================
// Ubicación: public/diccionario/index.php
// =====================================================================

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/iconos.php';

Auth::requiereSesion();

if (!Auth::puedeVerSeccion('diccionario')) {
    header('Location: ' . APP_URL . '/panel.php?error=sin_permiso');
    exit;
}

$titulo_pagina   = 'Diccionario de clases';
$puedeEditar     = Auth::tienePermiso('diccionario', 'editar');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <style>
        .tabs {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid var(--gris-borde);
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 18px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--ink-soft);
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: color 0.15s, border-color 0.15s;
            font-family: var(--fuente);
        }
        .tab:hover { color: var(--ink); }
        .tab.activo {
            color: var(--verde);
            border-bottom-color: var(--verde);
        }
        .tab .contador {
            background: var(--gris-claro);
            color: var(--ink-soft);
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-left: 6px;
        }
        .tab.activo .contador {
            background: var(--verde-claro);
            color: var(--verde);
        }
        .tab .contador.rojo {
            background: var(--rojo-claro);
            color: var(--rojo);
            font-weight: 600;
        }
        .tab-panel { display: none; }
        .tab-panel.activo { display: block; }
        .badge-tipo {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .tipo-Calibre  { background: #dbeafe; color: #1e40af; }
        .tipo-Calidad  { background: #dcfce7; color: #166534; }
        .tipo-Defecto  { background: #fee2e2; color: #991b1b; }
        .tipo-Granel   { background: #fef3c7; color: #92400e; }
        .tipo-Descarte { background: #f3e8ff; color: #6b21a8; }
        .tipo-Otro     { background: #e2e8f0; color: #475569; }
    </style>
</head>
<body>
    <div class="layout">
        <?php require_once __DIR__ . '/../../app/views/partials/sidebar.php'; ?>
        <?php require_once __DIR__ . '/../../app/views/partials/topbar.php'; ?>

        <main class="contenido">

            <div class="contenido-encabezado">
                <div>
                    <h2>Diccionario de clases</h2>
                    <p>Define el significado de los códigos de clase que entrega el equipo UNITEC.</p>
                </div>
                <?php if ($puedeEditar): ?>
                    <button class="boton boton-primario" id="btn-nueva-clase">
                        <?= icono('mas', 'icono-boton') ?>
                        <span>Nueva clase</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Pestañas -->
            <div class="tabs">
                <button class="tab activo" data-tab="faltantes">
                    Clases por definir
                    <span class="contador" id="tab-contador-faltantes">—</span>
                </button>
                <button class="tab" data-tab="diccionario">
                    Diccionario completo
                    <span class="contador" id="tab-contador-diccionario">—</span>
                </button>
            </div>

            <!-- Pestaña: Faltantes -->
            <div class="tab-panel activo" id="panel-faltantes">
                <div class="panel">
                    <div class="panel-encabezado">
                        <h3>Clases detectadas en los datos que aún no tienen definición</h3>
                        <button class="boton boton-borde boton-sm" id="btn-refrescar-faltantes">
                            <?= icono('refrescar', 'icono-boton') ?>
                            <span>Refrescar</span>
                        </button>
                    </div>

                    <div id="aviso-faltantes"></div>

                    <div class="tabla-wrapper" id="contenedor-faltantes">
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th style="width: 30px;"><input type="checkbox" id="check-todos-faltantes"></th>
                                    <th>Código</th>
                                    <th>Lotes</th>
                                    <th>Snapshots</th>
                                    <th>Frutas</th>
                                    <th>Kg</th>
                                    <th>Primera vez</th>
                                    <th>Última vez</th>
                                    <?php if ($puedeEditar): ?>
                                        <th style="text-align: right;">Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="tabla-faltantes">
                                <tr><td colspan="9" class="vacio">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pestaña: Diccionario completo -->
            <div class="tab-panel" id="panel-diccionario">
                <div class="panel">
                    <div class="panel-encabezado">
                        <h3>Clases definidas</h3>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem; color: var(--ink-soft); cursor: pointer;">
                                <input type="checkbox" id="chk-inactivas">
                                <span>Mostrar inactivas</span>
                            </label>
                            <button class="boton boton-borde boton-sm" id="btn-refrescar-diccionario">
                                <?= icono('refrescar', 'icono-boton') ?>
                                <span>Refrescar</span>
                            </button>
                        </div>
                    </div>

                    <div class="tabla-wrapper">
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Código</th>
                                    <th>Etiqueta</th>
                                    <th>Grupo</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <?php if ($puedeEditar): ?>
                                        <th style="text-align: right;">Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="tabla-diccionario">
                                <tr><td colspan="7" class="vacio">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: Crear / Editar clase                                    -->
    <!-- ============================================================= -->
    <div class="modal-fondo" id="modal-clase">
        <div class="modal" style="max-width: 620px;">
            <div class="modal-encabezado">
                <h3 id="modal-clase-titulo">Nueva clase</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-clase')">×</button>
            </div>

            <form id="form-clase" class="formulario">
                <input type="hidden" id="clase-id">

                <div class="campo-grid-2">
                    <div class="campo">
                        <label for="clase-codigo">Código (UNITEC) <span class="obligatorio">*</span></label>
                        <input type="text" id="clase-codigo" required maxlength="100"
                               placeholder="Ej: DESTRIO">
                        <p class="ayuda">Debe coincidir exactamente con el código que envía el equipo.</p>
                    </div>
                    <div class="campo">
                        <label for="clase-etiqueta">Etiqueta legible</label>
                        <input type="text" id="clase-etiqueta" maxlength="255"
                               placeholder="Ej: Destrío / descarte">
                    </div>
                </div>

                <div class="campo-grid-2">
                    <div class="campo">
                        <label for="clase-grupo">Grupo</label>
                        <input type="text" id="clase-grupo" maxlength="100"
                               placeholder="Ej: Defectos, Tipo Grande...">
                        <p class="ayuda">Sirve para agrupar clases en reportes.</p>
                    </div>
                    <div class="campo">
                        <label for="clase-tipo">Tipo</label>
                        <select id="clase-tipo">
                            <option value="Otro">Otro</option>
                            <option value="Calibre">Calibre</option>
                            <option value="Calidad">Calidad</option>
                            <option value="Defecto">Defecto</option>
                            <option value="Granel">Granel</option>
                            <option value="Descarte">Descarte</option>
                        </select>
                    </div>
                </div>

                <div class="campo-grid-2">
                    <div class="campo">
                        <label for="clase-orden">Orden</label>
                        <input type="number" id="clase-orden" value="100" min="0" max="9999">
                        <p class="ayuda">Menor número = aparece primero.</p>
                    </div>
                    <div class="campo">
                        <label for="clase-color">Color (opcional)</label>
                        <input type="text" id="clase-color" maxlength="20" placeholder="#3b82f6">
                    </div>
                </div>

                <div class="campo">
                    <label for="clase-notas">Notas</label>
                    <textarea id="clase-notas" rows="2"></textarea>
                </div>

                <div class="campo">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="clase-activo" checked>
                        <span>Activa (visible en reportes)</span>
                    </label>
                </div>
            </form>

            <div class="modal-acciones">
                <button type="button" class="boton boton-borde" onclick="cerrarModal('modal-clase')">Cancelar</button>
                <button type="button" class="boton boton-primario" id="btn-guardar-clase">
                    <?= icono('guardar', 'icono-boton') ?>
                    <span>Guardar</span>
                </button>
            </div>
        </div>
    </div>

    <script>const BASE_URL = '<?= APP_URL ?>';</script>
    <script>const PUEDE_EDITAR = <?= $puedeEditar ? 'true' : 'false' ?>;</script>
    <script src="<?= APP_URL ?>/assets/js/api.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=<?= ASSETS_VERSION ?>"></script>
    <script src="<?= APP_URL ?>/assets/js/diccionario.js?v=<?= ASSETS_VERSION ?>"></script>
</body>
</html>