<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

include_once '../../config/conexion.php';
include_once '../../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->correo) || empty($data->password)) {
    echo json_encode(["success" => false, "message" => "Faltan credenciales."]);
    exit;
}

$usuario = new Usuario($db);
$resultado = $usuario->login($data->correo, $data->password);

if (is_array($resultado) && isset($resultado["error"]) && $resultado["error"] === "inactivo") {
    echo json_encode([
        "success" => false,
        "message" => "Tu cuenta está inactiva. Contacta al administrador."
    ]);
    exit;
}

if ($resultado && !isset($resultado["error"])) {
    echo json_encode([
        "success" => true,
        "message" => "Login exitoso.",
        "data" => [
            "id" => $resultado['id'],
            "nombre" => $resultado['nombre'],
            "apellido" => $resultado['apellido'],
            "correo" => $resultado['correo'],
            "rol" => $resultado['rol'],
            "id_empresa" => $resultado['id_empresa'],        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Correo o contraseña incorrectos."]);
}
?>
