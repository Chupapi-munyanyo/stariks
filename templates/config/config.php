<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stariks');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3307');
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=".DB_PORT.";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            
           
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`;");
            $this->pdo->exec("USE `" . DB_NAME . "`;");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

function getDB() {
    return Database::getInstance()->getConnection();
}
?>
