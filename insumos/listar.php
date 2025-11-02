<?php
require_once '../config/cors.php'; // Habilitar CORS
include_once '../config/conexion.php';
include_once '../models/insumo.php'; // Incluir el modelo

$database = new Database();
$db = $database->getConnection();
$insumo = new Insumo($db);

// Obtener el ID de sede (priorizando GET)
$id_sede = isset($_GET['id_sede']) ? $_GET['id_sede'] : null;
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : null;

if (empty($id_sede)) {
    // Intentar leer del cuerpo JSON si no viene por GET
    $data = json_decode(file_get_contents("php://input"));
    if (isset($data->id_sede)) {
        $id_sede = $data->id_sede;
    }
}

if (empty($id_sede)) {
    echo json_encode(["success" => false, "message" => "Falta el id de la sede."]);
    exit;
}

// Listar
$stmt = $insumo->listar($id_sede, $filtro);

if ($stmt->rowCount() > 0) {
    $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "data" => $insumos]);
} else {
    echo json_encode(["success" => true, "data" => [], "message" => "No hay insumos registrados para esta sede."]);
}
?>