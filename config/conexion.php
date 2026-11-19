<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Lee las variables de entorno en Render; si no existen, usa tus datos locales por defecto
        $this->host     = getenv('DB_HOST') ?: "localhost";
        $this->port     = getenv('DB_PORT') ?: "3306";
        $this->db_name  = getenv('DB_NAME') ?: "inventivo_db";
        $this->username = getenv('DB_USER') ?: "root";
        $this->password = getenv('DB_PASS') ?: "";
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8mb4");
            
            // Habilitar excepciones de PDO
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

        } catch(PDOException $exception) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                "success" => false, 
                "message" => "Error de conexión: " . $exception->getMessage()
            ]);
            exit;
        }

        return $this->conn;
    }
}
?>