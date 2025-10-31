<?php
require_once '../../config/conexion.php';
require_once '../../config/cors.php';
require_once '../../controllers/facturaController.php';

// Manejar preflight OPTIONS (si es necesario para navegadores)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$controller = new FacturaController();

// 1. Obtener el id_empresa del parámetro GET
$id_empresa = isset($_GET['id_empresa']) ? intval($_GET['id_empresa']) : null;

if (empty($id_empresa)) {
    echo json_encode(["success" => false, "message" => "Falta el parámetro id_empresa."]);
    exit;
}

// 2. Llamar al controlador y devolver la respuesta JSON
$response = $controller->obtenerSiguienteNumeroFactura($id_empresa);

echo json_encode($response);
?>