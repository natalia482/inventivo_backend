<?php
require_once '../../controllers/facturaController.php';
require_once '../../config/cors.php';

$id = $_GET['id'];
$controller = new FacturaController();
$controller->eliminarFactura($id);
?>
