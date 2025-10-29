<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

include_once '../../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// 🔹 Verificamos si llegaron los campos necesarios
if (empty($data->id)) {
    echo json_encode(["success" => false, "message" => "Falta el ID del trabajador."]);
    exit;
}

// 🔹 Preparamos los campos que se van a actualizar
$updateFields = [];
$params = [];

if (!empty($data->nombre)) {
    $updateFields[] = "nombre = :nombre";
    $params[':nombre'] = $data->nombre;
}
if (!empty($data->apellido)) {
    $updateFields[] = "apellido = :apellido";
    $params[':apellido'] = $data->apellido;
}
if (!empty($data->correo)) {
    $updateFields[] = "correo = :correo";
    $params[':correo'] = $data->correo;
}
if (!empty($data->password)) {
    // 🔐 Se encripta la nueva contraseña
    $updateFields[] = "password = :password";
    $params[':password'] = password_hash($data->password, PASSWORD_BCRYPT);
}

// 🔹 Si no hay campos para actualizar
if (empty($updateFields)) {
    echo json_encode(["success" => false, "message" => "No hay campos para actualizar."]);
    exit;
}

// 🔹 Armamos la consulta SQL dinámica
$query = "UPDATE usuarios SET " . implode(", ", $updateFields) . " WHERE id = :id AND rol = 'TRABAJADOR'";
$stmt = $db->prepare($query);
$params[':id'] = $data->id;

// 🔹 Ejecutamos
if ($stmt->execute($params)) {
    echo json_encode(["success" => true, "message" => "Datos del trabajador actualizados correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar los datos del trabajador."]);
}
?>
