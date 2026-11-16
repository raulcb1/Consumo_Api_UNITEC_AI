<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/auth.php';

Auth::requiereSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - SCPC</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
</head>
<body style="display:block; padding:40px;">
    <div style="max-width:800px; margin:0 auto; background:white; padding:40px; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.1);">
        <h1 style="color:var(--verde);">✅ ¡Bienvenido, <?= htmlspecialchars($_SESSION['empleado_nombre'] ?? $_SESSION['usuario']) ?>!</h1>
        <p>Has iniciado sesión correctamente en el <strong><?= APP_NOMBRE ?></strong>.</p>
        <p><strong>Rol asignado:</strong> <?= $_SESSION['rol'] ?? 'Sin rol' ?></p>
        <p><strong>Usuario ID:</strong> <?= $_SESSION['usuario_id'] ?></p>
        <hr style="margin:20px 0;">
        <a href="<?= APP_URL ?>/logout.php" style="background:var(--rojo); color:white; padding:10px 20px; border-radius:8px; text-decoration:none;">Cerrar Sesión</a>
    </div>
</body>
</html>