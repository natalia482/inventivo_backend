<?php
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