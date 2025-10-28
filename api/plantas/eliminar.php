<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/conexion.php';
include_once '../../controllers/PlantasController.php';

$database = new Database();
$db = $database->getConnection();

$controller = new PlantaController($db);

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"])) {
    echo json_encode(["success" => false, "message" => "Falta el ID de la planta."]);
    exit;
}

$id = intval($data["id"]);

$result = $controller->eliminar($id);

if ($result["success"]) {
    echo json_encode(["success" => true, "message" => $result["message"]]);
} else {
    echo json_encode(["success" => false, "message" => $result["message"]]);
}
?>