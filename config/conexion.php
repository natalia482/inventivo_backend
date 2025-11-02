<?php
class Database {
    private $host = "localhost";
    private $db_name = "u853019229_inventivo_db";
    private $username = "u853019229_inventivo_user";
    private $password = "Inventivo123";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8mb4");
            
            // CORRECCIÓN: Habilitar excepciones de PDO
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

        } catch(PDOException $exception) {
            echo json_encode(["success" => false, "message" => "Error de conexión: " . $exception->getMessage()]);
            exit;
        }

        return $this->conn;
    }
}
?>