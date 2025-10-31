<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/conexion.php';
require_once '../../controllers/FacturaController.php';

$db = (new Database())->getConnection();
$controller = new FacturaController($db);

if (isset($_GET['id_empresa'])) {
    $id_empresa = intval($_GET['id_empresa']);
    $response = $controller->obtenerSiguienteNumeroFactura($id_empresa);
    echo json_encode($response);
} else {
    echo json_encode(["success" => false, "message" => "Falta el parámetro id_empresa"]);
}
?>