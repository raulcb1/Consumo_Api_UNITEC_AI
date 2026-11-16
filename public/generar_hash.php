<?php
// =====================================================================
// generar_hash.php - TEMPORAL: generador interactivo de hashes bcrypt
// =====================================================================
// ATENCIÓN: Elimina este archivo cuando termines de usarlo.
// Solo funciona en entorno 'local' (ver config.php).
// =====================================================================

require_once __DIR__ . '/../app/config/config.php';

// Bloquear en producción
if (APP_ENV !== 'local') {
    http_response_code(403);
    die('Este script solo está disponible en entorno local.');
}

// Procesar el formulario
$texto     = '';
$hash      = '';
$verificar = '';
$coincide  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texto     = $_POST['texto'] ?? '';
    $verificar = $_POST['verificar'] ?? '';

    if ($texto !== '') {
        $hash = password_hash($texto, PASSWORD_DEFAULT);
    }

    // Si el usuario pega un hash para verificar, lo comprobamos
    if ($verificar !== '' && $texto !== '') {
        $coincide = password_verify($texto, $verificar);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Hash - SCPC (solo local)</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/panel.css?v=<?= ASSETS_VERSION ?>">
    <style>
        body { display: block; padding: 40px 20px; background: var(--arena); }
        .contenedor { max-width: 720px; margin: 0 auto; }
        .panel { background: var(--blanco); border-radius: var(--radio); padding: 28px; box-shadow: var(--sombra-media); }
        .panel h1 { font-size: 1.5rem; color: var(--verde); margin-bottom: 6px; }
        .panel .aviso { background: var(--rojo-claro); color: var(--rojo); padding: 12px 16px; border-radius: 8px; border-left: 4px solid var(--rojo); margin-bottom: 20px; font-size: 0.9rem; }
        .resultado { background: var(--gris-claro); border: 1px solid var(--gris-borde); border-radius: 8px; padding: 16px; margin-top: 16px; word-break: break-all; font-family: 'Courier New', monospace; font-size: 0.85rem; }
        .resultado strong { color: var(--verde); font-family: var(--fuente); }
        .ok  { color: var(--verde); font-weight: 600; }
        .mal { color: var(--rojo); font-weight: 600; }
    </style>
</head>
<body>
<div class="contenedor">
    <div class="panel">
        <h1>Generador de Hash</h1>
        <p style="color: var(--ink-soft); margin-bottom: 20px;">
            Introduce el texto que quieres hashear (normalmente una contraseña).
        </p>

        <div class="aviso">
            ⚠️ Este script solo está disponible en entorno <strong>local</strong>.<br>
            Elimínalo cuando ya no lo necesites.
        </div>

        <form method="POST">
            <div class="campo">
                <label for="texto">Texto a hashear <span class="obligatorio">*</span></label>
                <input type="text" id="texto" name="texto" 
                       value="<?= htmlspecialchars($texto) ?>" 
                       placeholder="Ej: admin123" 
                       required autofocus>
            </div>

            <div class="campo" style="margin-top: 14px;">
                <label for="verificar">Verificar contra un hash (opcional)</label>
                <input type="text" id="verificar" name="verificar" 
                       value="<?= htmlspecialchars($verificar) ?>" 
                       placeholder="Pega aquí un hash existente para comprobar si coincide">
            </div>

            <button type="submit" class="boton boton-primario" style="margin-top: 18px;">
                Generar Hash
            </button>
        </form>

        <?php if ($hash !== ''): ?>
            <div class="resultado">
                <strong>✅ Hash generado:</strong><br>
                <?= htmlspecialchars($hash) ?>
            </div>

            <div class="resultado" style="margin-top: 10px;">
                <strong>📋 Para copiar a un SQL:</strong><br>
                UPDATE usuarios_sistema<br>
                SET password_hash = '<?= htmlspecialchars($hash) ?>'<br>
                WHERE usuario = 'admin';
            </div>
        <?php endif; ?>

        <?php if ($coincide !== null): ?>
            <div class="resultado" style="margin-top: 10px;">
                <strong>Verificación contra el hash:</strong><br>
                <?php if ($coincide): ?>
                    <span class="ok">✔ El texto COINCIDE con el hash</span>
                <?php else: ?>
                    <span class="mal">✖ El texto NO coincide con el hash</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--gris-claro);">

        <p style="font-size: 0.85rem; color: var(--ink-soft);">
            <strong>Nota:</strong> Cada vez que generes un hash para el mismo texto, obtendrás un resultado distinto 
            (por el "salt" aleatorio). Todos son válidos para ese texto. Puedes pegar cualquiera en la base de datos.
        </p>

        <p style="margin-top: 16px;">
            <a href="<?= APP_URL ?>/login.php" class="boton boton-secundario">← Volver al login</a>
        </p>
    </div>
</div>
</body>
</html>