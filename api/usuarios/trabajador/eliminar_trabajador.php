<?php
header("Content-Type: application/json");
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
include_once "../../../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["id"])) {
        echo json_encode(["success" => false, "message" => "Falta el ID"]);
        exit;
    }

    $id = $data["id"];
    $database = new Database();
    $conexion = $database->getConnection();

    $query = "DELETE FROM usuarios WHERE id = :id AND rol = 'TRABAJADOR'";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(":id", $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Trabajador eliminado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar el trabajador"]);
    }
}
?>
