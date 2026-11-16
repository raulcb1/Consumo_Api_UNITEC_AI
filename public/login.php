<?php
// =====================================================================
// login.php - Página de autenticación del sistema SCPC
// =====================================================================
// Ubicación: public/login.php
// =====================================================================

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/iconos.php';

// ---------------------------------------------------------------------
// 1. PROCESAR EL FORMULARIO DE LOGIN (POST)
// ---------------------------------------------------------------------
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../app/controllers/authcontroller.php';

    $resultado = AuthController::login(
        $_POST['usuario']  ?? '',
        $_POST['password'] ?? ''
    );

    if ($resultado['ok']) {
        header('Location: ' . APP_URL . '/panel.php');
        exit;
    } else {
        $mensaje_error = $resultado['mensaje'];
    }
}

// ---------------------------------------------------------------------
// 2. SI YA ESTÁ AUTENTICADO, REDIRIGIR AL PANEL
// ---------------------------------------------------------------------
if (Auth::estaAutenticado()) {
    header('Location: ' . APP_URL . '/panel.php');
    exit;
}

// ---------------------------------------------------------------------
// 3. MENSAJE DE ERROR PROVENIENTE DE LA URL (?error=...)
// ---------------------------------------------------------------------
if (empty($mensaje_error) && isset($_GET['error'])) {
    $errores = [
        'credenciales_vacias'    => 'Por favor, ingresa tu usuario y contraseña.',
        'credenciales_invalidas' => 'Usuario o contraseña incorrectos.',
        'sin_permiso'            => 'No tienes permisos para acceder a esa sección.',
        'sesion_expirada'        => 'Tu sesión ha expirado. Vuelve a iniciar sesión.'
    ];
    $mensaje_error = $errores[$_GET['error']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
</head>
<body class="login-body">
    <div class="login-container">
        <h1><?= APP_NOMBRE ?></h1>
        <p class="slogan"><?= SLOGAN ?></p>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alerta alerta-error">
                <?= htmlspecialchars($mensaje_error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/login.php">
            <div class="campo">
                <label for="usuario">Usuario</label>
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    placeholder="Ingresa tu usuario"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Ingresa tu contraseña"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit">Ingresar al sistema</button>
        </form>

        <p class="footer-text">
            <?= APP_NOMBRE ?> &middot; v1.0
        </p>
    </div>
</body>
</html>