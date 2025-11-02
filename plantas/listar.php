<?php
include_once '../config/cors.php';
include_once '../config/conexion.php';
include_once '../controllers/PlantasController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new PlantaController($db);

// Parámetros (Modificado a id_sede)
$id_sede = isset($_GET['id_sede']) ? $_GET['id_sede'] : null;
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : null;

if (!$id_sede) {
    echo json_encode(["success" => false, "message" => "Falta el id_sede."]);
    exit;
}

// Llamar al método listar
$response = $controller->listar($id_sede, $filtro);

echo json_encode($response);
?>