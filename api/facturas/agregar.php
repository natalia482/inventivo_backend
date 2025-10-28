<?php
require_once '../../controllers/facturaController.php';
require_once '../../config/cors.php';

$data = json_decode(file_get_contents("php://input"), true);
$controller = new FacturaController();
$controller->agregarFactura($data);
?>
