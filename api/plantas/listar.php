<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/conexion.php';
include_once '../../controllers/PlantasController.php';

// Crear la conexión
$database = new Database();
$db = $database->getConnection();

// Crear el controlador
$controller = new PlantaController($db);

// Parámetros
$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null;
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : null;

if (!$id_empresa) {
    echo json_encode(["success" => false, "message" => "Falta el id_empresa."]);
    exit;
}

// Llamar al método listar (puede recibir o no un filtro)
$response = $controller->listar($id_empresa, $filtro);

echo json_encode($response);
?>
