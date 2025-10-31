<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

require_once("../../controllers/facturaController.php");

if (!isset($_GET['id_factura'])) {
    echo json_encode(["success" => false, "message" => "Falta id_factura"]);
    exit;
}

$id_factura = intval($_GET['id_factura']);
$controller = new FacturaController();
$response = $controller->obtenerDetalleFactura($id_factura);

echo json_encode($response);
?>