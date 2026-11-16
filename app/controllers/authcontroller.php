<?php
// =====================================================================
// AuthController: conecta el formulario de login con la lógica de Auth.
// =====================================================================
// Uso desde public/login.php (cuando se envía el formulario):
//   $resultado = AuthController::login($_POST['usuario'] ?? '', $_POST['password'] ?? '');
//   if ($resultado['ok']) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }
// =====================================================================

require_once __DIR__ . '/../core/auth.php';

class AuthController
{
    /**
     * Procesa el intento de login.
     *
     * @param string $usuario
     * @param string $password
     * @return array ['ok' => bool, 'mensaje' => string]
     */
    public static function login(string $usuario, string $password): array
    {
        $usuario = trim($usuario);

        if ($usuario === '' || $password === '') {
            return [
                'ok'      => false,
                'mensaje' => 'Usuario y contraseña son obligatorios.',
            ];
        }

        $exito = Auth::intentarLogin($usuario, $password);

        if (!$exito) {
            return [
                'ok'      => false,
                'mensaje' => 'Usuario o contraseña incorrectos, o la cuenta no está activa.',
            ];
        }

        return [
            'ok'      => true,
            'mensaje' => 'Login exitoso.',
        ];
    }

    /**
     * Cierra la sesión del usuario.
     */
    public static function logout(): void
    {
        Auth::cerrarSesion();
    }
}