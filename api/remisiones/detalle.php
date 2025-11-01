<?php
require_once("../../config/cors.php");
require_once("../../config/conexion.php");
require_once("../../controllers/RemisionController.php");

if (!isset($_GET['id_Remision'])) {
    echo json_encode(["success" => false, "message" => "Falta id_Remision"]);
    exit;
}

$id_Remision = intval($_GET['id_Remision']);
$controller = new RemisionController();
$response = $controller->obtenerDetalleRemision($id_Remision);

echo json_encode($response);
?>