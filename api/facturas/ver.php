<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/Conexion.php';
require_once '../../controllers/FacturaController.php';

$db = (new Database())->getConnection();
$controller = new FacturaController($db);

if (isset($_GET['id'])) {
    $factura = $controller->obtenerFactura($_GET['id']);
    echo json_encode($factura);
} else {
    echo json_encode(["success" => false, "message" => "Falta el parámetro id"]);
}
?>
