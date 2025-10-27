<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

include_once '../../../config/conexion.php';
include_once '../../../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Verificación básica de datos
if (
    empty($data->nombre) ||
    empty($data->apellido) ||
    empty($data->correo) ||
    empty($data->password) ||
    empty($data->id_empresa)
) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios."]);
    exit;
}

// Crear usuario trabajador
$usuario = new Usuario($db);
$usuario->nombre = $data->nombre;
$usuario->apellido = $data->apellido;
$usuario->correo = $data->correo;
$usuario->password = password_hash($data->password, PASSWORD_BCRYPT);
$usuario->rol = "TRABAJADOR";
$usuario->id_empresa = $data->id_empresa;

if ($usuario->crear()) {
    echo json_encode([
        "success" => true,
        "message" => "Trabajador registrado correctamente.",
        "id_usuario" => $usuario->id,
        "id_empresa" => $usuario->id_empresa
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Error al registrar el trabajador."]);
}
?>
