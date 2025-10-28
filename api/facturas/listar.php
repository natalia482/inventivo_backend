<?php
require_once '../../controllers/facturaController.php';
require_once '../../config/cors.php';

$id_empresa = $_GET['id_empresa'];
$controller = new FacturaController();
$controller->listarFacturas($id_empresa);
?>
