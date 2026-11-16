<?php
// app/config/database.php

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET . ';port=' . DB_PORT;
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // En local mostramos error, en producción lo logueamos
            if (APP_ENV === 'local') {
                die('Error de conexión a la base de datos: ' . $e->getMessage());
            } else {
                error_log('Error de conexión DB: ' . $e->getMessage());
                die('Error interno del servidor. Contacte al administrador.');
            }
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
    // app/config/database.php (agregar al final de la clase)
    public static function getConexion(): PDO
    {
        return self::getInstance()->getConnection();
    }
}
