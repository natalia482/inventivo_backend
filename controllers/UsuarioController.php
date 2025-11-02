<?php
require_once "../config/conexion.php";
require_once "../models/Usuario.php";

class UsuarioController {
    private $db;
    private $usuario;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuario = new Usuario($this->db);
    }

    // Registro de usuario
    public function registrar($data) {
        if (
            empty($data['nombre']) ||
            empty($data['apellido']) ||
            empty($data['correo']) ||
            empty($data['password']) ||
            empty($data['rol'])
        ) {
            return ["success" => false, "message" => "Faltan datos obligatorios."];
        }

        $this->usuario->nombre = htmlspecialchars(strip_tags($data['nombre']));
        $this->usuario->apellido = htmlspecialchars(strip_tags($data['apellido']));
        $this->usuario->correo = htmlspecialchars(strip_tags($data['correo']));
        $this->usuario->password = $data['password'];
        $this->usuario->rol = htmlspecialchars(strip_tags($data['rol']));

        if ($this->usuario->existeCorreo()) {
            return ["success" => false, "message" => "El correo ya está registrado."];
        }

        if ($this->usuario->registrar()) {
            return ["success" => true, "message" => "Usuario registrado exitosamente."];
        } else {
            return ["success" => false, "message" => "Error al registrar el usuario."];
        }
    }

    //Login de usuario
    public function login($data) {

        $database = new Database();
        $db = $database->getConnection();

        $usuario = new Usuario($db);

        if (empty($data['correo']) || empty($data['password'])) {
            return [
                "success" => false,
                "message" => "Faltan datos obligatorios"
            ];
        }

        $resultado = $usuario->loginUsuario($data['correo'], $data['password']);

        if ($resultado) {
            return [
                "success" => true,
                "message" => "Inicio de sesión exitoso",
                "usuario" => $resultado
            ];
        } else {
            return [
                "success" => false,
                "message" => "Credenciales incorrectas"
            ];
        }
    }
}
?>
