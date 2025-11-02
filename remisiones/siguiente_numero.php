<?php
require_once '../config/conexion.php';
require_once '../config/cors.php';
require_once '../controllers/RemisionController.php';

// Manejar preflight OPTIONS (si es necesario para navegadores)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$controller = new RemisionController();

// 1. Obtener el id_empresa del parámetro GET
$id_empresa = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : null;

if (empty($id_empresa)) {
    echo json_encode(["success" => false, "message" => "Falta el parámetro id_sede."]);
    exit;
}

// 2. Llamar al controlador y devolver la respuesta JSON
$response = $controller->obtenerSiguienteNumeroRemision($id_empresa);

echo json_encode($response);
?>