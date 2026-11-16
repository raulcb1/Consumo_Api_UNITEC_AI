<?php
// public/404.php
// Página personalizada de error 404

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/iconos.php';

// Si hay sesión activa, mostramos el layout del sistema.
// Si no hay sesión, mostramos una versión simple.
$logueado = Auth::estaAutenticado();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada (404) - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <style>
        .error-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            padding: 20px;
        }

        .error-card {
            background: var(--blanco);
            border-radius: 16px;
            box-shadow: var(--sombra-media);
            padding: 48px 40px;
            text-align: center;
            max-width: 520px;
            border-top: 6px solid var(--naranja);
        }

        .error-code {
            font-size: 6rem;
            font-weight: 800;
            color: var(--naranja);
            line-height: 1;
            margin-bottom: 8px;
            letter-spacing: -2px;
        }

        .error-titulo {
            font-size: 1.5rem;
            color: var(--ink);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .error-mensaje {
            color: var(--ink-soft);
            font-size: 0.95rem;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .error-acciones {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            color: var(--naranja);
            margin-bottom: 12px;
            opacity: 0.85;
        }
    </style>
</head>

<body <?= $logueado ? '' : 'class="login-body"' ?>>

    <?php if ($logueado): ?>
        <div class="layout">
            <?php require_once __DIR__ . '/../app/views/partials/sidebar.php'; ?>
            <?php require_once __DIR__ . '/../app/views/partials/topbar.php'; ?>
            <main class="contenido">
                <div class="error-container">
                    <div class="error-card">
                        <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            <line x1="8" y1="11" x2="14" y2="11" />
                        </svg>
                        <div class="error-code">404</div>
                        <h1 class="error-titulo">Página no encontrada</h1>
                        <p class="error-mensaje">
                            La página que estás buscando no existe, fue movida o está en desarrollo.
                            Verifica la URL o regresa al panel principal.
                        </p>
                        <div class="error-acciones">
                            <a href="<?= APP_URL ?>/panel.php" class="boton boton-primario">Volver al Panel</a>
                            <a href="javascript:history.back()" class="boton boton-borde">Regresar</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    <?php else: ?>
        <div class="login-container" style="max-width: 520px;">
            <div class="error-card" style="box-shadow: none; padding: 20px; border-radius: 0; border-top: none;">
                <div class="error-code">404</div>
                <h1 class="error-titulo">Página no encontrada</h1>
                <p class="error-mensaje">
                    La página que intentas acceder no existe o fue removida.
                </p>
                <div class="error-acciones">
                    <a href="<?= APP_URL ?>/login.php" class="boton boton-primario">Ir al Login</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</body>

</html>