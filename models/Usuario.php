<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nombre;
    public $apellido;
    public $correo;
    public $password;
    public $rol;
    public $id_sede; // Modificado

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  (nombre, apellido, correo, password, rol, id_sede)
                  VALUES (:nombre, :apellido, :correo, :password, :rol, :id_sede)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":id_sede", $this->id_sede); // Modificado

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function existeCorreo($correo){
        $query = "SELECT id FROM usuarios WHERE correo = :correo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":correo", $correo);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function login($correo, $password) {
        // Unimos con sedes para obtener también el id_empresa
        $query = "SELECT u.id, u.nombre, u.apellido, u.correo, u.password, u.rol, u.id_sede, u.estado, s.id_empresa
                  FROM usuarios u
                  LEFT JOIN sedes s ON u.id_sede = s.id
                  WHERE u.correo = :correo LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (isset($usuario['estado']) && $usuario['estado'] === 'INACTIVO') {
                return ["error" => "inactivo"];
            }

            if (password_verify($password, $usuario['password'])) {
                return $usuario;
            }
        }
        return false;
    }
}
?>