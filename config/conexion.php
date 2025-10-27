<?php
class Database {
    private $host = "localhost";
    private $db_name = "bd_inventivo";
    private $username = "root";
    private $password = "";
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
        } catch(PDOException $exception) {
            echo json_encode(["success" => false, "message" => "Error de conexión: " . $exception->getMessage()]);
            exit;
        }

        return $this->conn;
    }
}
?>