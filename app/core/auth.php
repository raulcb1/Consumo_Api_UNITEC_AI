<?php
// =====================================================================
// Auth: maneja login, sesión y verificación de rol.
// =====================================================================
// Uso en una página protegida (arriba del todo, antes de imprimir nada):
//   Auth::requiereSesion();                        // cualquier usuario logueado
//   Auth::requiereRol(['admin']);                  // solo admin
//   Auth::requiereRol(['admin', 'supervisor']);    // admin o supervisor
// =====================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Auth
{
    /**
     * Inicia la sesión si no está iniciada.
     */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Intenta autenticar un usuario con usuario y contraseña.
     *
     * @param string $usuario
     * @param string $password
     * @return bool True si el login es exitoso, false en caso contrario.
     */
    public static function intentarLogin(string $usuario, string $password): bool
    {
        self::iniciar();
        $pdo = Database::getConexion();

        $sql = "SELECT 
                    us.id, 
                    us.password_hash, 
                    us.activo, 
                    us.fecha_expiracion,
                    us.empleado_id, 
                    r.nombre AS rol,
                    e.nombre AS empleado_nombre
                FROM usuarios_sistema us
                JOIN roles r ON r.id = us.rol_id
                LEFT JOIN empleados e ON e.id = us.empleado_id
                WHERE us.usuario = :usuario
                  AND us.deleted_at IS NULL
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        $fila = $stmt->fetch();

        if (!$fila) {
            return false; // el usuario no existe o está eliminado
        }

        if ((int) $fila['activo'] !== 1) {
            return false; // cuenta desactivada
        }

        if ($fila['fecha_expiracion'] !== null && $fila['fecha_expiracion'] < date('Y-m-d')) {
            return false; // cuenta temporal vencida
        }

        if (!password_verify($password, $fila['password_hash'])) {
            return false; // contraseña incorrecta
        }

        // Guardar datos en sesión
        $_SESSION['usuario_id']      = (int) $fila['id'];
        $_SESSION['rol']             = $fila['rol'];
        $_SESSION['empleado_id']     = $fila['empleado_id'] ? (int) $fila['empleado_id'] : null;
        $_SESSION['empleado_nombre'] = $fila['empleado_nombre'] ?? null;
        $_SESSION['usuario']         = $usuario;

        // Actualizar último acceso
        $actualizar = $pdo->prepare('UPDATE usuarios_sistema SET ultimo_acceso = NOW() WHERE id = :id');
        $actualizar->execute(['id' => $fila['id']]);

        return true;
    }

    /**
     * Cierra la sesión del usuario.
     */
    public static function cerrarSesion(): void
    {
        self::iniciar();
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Verifica si el usuario está autenticado.
     */
    public static function estaAutenticado(): bool
    {
        self::iniciar();
        return isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0;
    }

    /**
     * Exige que el usuario esté autenticado; si no, redirige al login.
     */
    public static function requiereSesion(): void
    {
        self::iniciar();
        if (!self::estaAutenticado()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    /**
     * Exige que el usuario tenga uno de los roles permitidos.
     *
     * @param array $rolesPermitidos Lista de nombres de roles (ej. ['admin', 'supervisor'])
     */
    public static function requiereRol(array $rolesPermitidos): void
    {
        self::requiereSesion();
        if (!in_array($_SESSION['rol'], $rolesPermitidos, true)) {
            http_response_code(403);
            die('No tienes permiso para acceder a esta sección.');
        }
    }

    /**
     * Devuelve los datos del usuario actual.
     */
    public static function usuarioActual(): array
    {
        self::iniciar();
        return [
            'usuario_id'      => $_SESSION['usuario_id']      ?? null,
            'rol'             => $_SESSION['rol']             ?? null,
            'empleado_id'     => $_SESSION['empleado_id']     ?? null,
            'empleado_nombre' => $_SESSION['empleado_nombre'] ?? null,
            'usuario'         => $_SESSION['usuario']         ?? null,
        ];
    }

    /**
     * Verifica si el usuario actual tiene permiso para realizar una acción en una sección.
     *
     * @param string $seccion Identificador de la sección (ej. 'lineas', 'lotes')
     * @param string $accion Acción a verificar (ej. 'crear', 'editar', 'ver')
     */
    public static function tienePermiso(string $seccion, string $accion): bool
    {
        self::iniciar();

        if (!isset($_SESSION['rol'])) {
            return false;
        }

        $rol = $_SESSION['rol'];
        $permisos = PERMISOS; // constante definida en config.php

        if (!isset($permisos['secciones'][$seccion])) {
            return false;
        }

        if (!isset($permisos['secciones'][$seccion]['permisos'][$accion])) {
            return false;
        }

        $rolesPermitidos = $permisos['secciones'][$seccion]['permisos'][$accion];
        return in_array($rol, $rolesPermitidos, true);
    }

    /**
     * Verifica si el usuario tiene acceso a una sección (para mostrar en menú).
     */
    public static function puedeVerSeccion(string $seccion): bool
    {
        self::iniciar();

        if (!isset($_SESSION['rol'])) {
            return false;
        }

        $rol = $_SESSION['rol'];
        $permisos = PERMISOS;

        if (!isset($permisos['secciones'][$seccion])) {
            return false;
        }

        return in_array($rol, $permisos['secciones'][$seccion]['roles'], true);
    }

    /**
     * Valida que la sesión temporal de acceso (sin login) sea válida y no haya expirado.
     */
    public static function validarAccesoTemporal(): bool
    {
        self::iniciar();
        if (!isset($_SESSION['acceso_temporal']) || !isset($_SESSION['empleado_id'])) {
            return false;
        }
        if (!isset($_SESSION['acceso_expiracion'])) {
            $_SESSION['acceso_expiracion'] = time() + 1800;
            return true;
        }
        if (time() > $_SESSION['acceso_expiracion']) {
            unset($_SESSION['acceso_temporal']);
            unset($_SESSION['empleado_id']);
            unset($_SESSION['acceso_expiracion']);
            return false;
        }
        return true;
    }
}