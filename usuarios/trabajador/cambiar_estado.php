<?php
header('Content-Type: application/json');
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !isset($data['estado'])) {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

$id = $data['id'];
$estado = $data['estado'];

$query = "UPDATE usuarios SET estado = :estado WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":estado", $estado);
$stmt->bindParam(":id", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Estado actualizado correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar estado"]);
}
?>
