<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    echo json_encode(["success" => false, "message" => "Falta el ID del insumo."]);
    exit;
}

$query = "UPDATE insumos SET cantidad = 0 WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $data->id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Insumo marcado como no disponible."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar el insumo."]);
}
?>
