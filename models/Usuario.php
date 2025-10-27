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
    public $id_empresa;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  (nombre, apellido, correo, password, rol, id_empresa)
                  VALUES (:nombre, :apellido, :correo, :password, :rol, :id_empresa)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":id_empresa", $this->id_empresa);

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

    public function registrar($data)
    {
    $query = "INSERT INTO usuarios (nombre, apellido, correo, password, rol, id_empresa, nombre_empresa)
              VALUES (:nombre, :apellido, :correo, :password, :rol, :id_empresa, :nombre_empresa)";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":nombre", $data['nombre']);
    $stmt->bindParam(":apellido", $data['apellido']);
    $stmt->bindParam(":correo", $data['correo']);
    $stmt->bindParam(":password", $data['password']);
    $stmt->bindParam(":rol", $data['rol']);
    $stmt->bindParam(":id_empresa", $data['id_empresa']);
    $stmt->bindParam(":nombre_empresa", $data['nombre_empresa']);
    return $stmt->execute();
    }
    
    public function login($correo, $password) {
    $query = "SELECT * FROM usuarios WHERE correo = :correo LIMIT 1";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':correo', $correo);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 🔹 Validar estado del usuario
        if (isset($usuario['estado']) && $usuario['estado'] === 'INACTIVO') {
            // Devolvemos un mensaje especial que será manejado en login.php
            return ["error" => "inactivo"];
        }

        // 🔹 Verificar la contraseña
        if (password_verify($password, $usuario['password'])) {
            return $usuario;
        }
    }

    return false; // Usuario no encontrado o contraseña incorrecta
}
}
?>
