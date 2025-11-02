<?php
require_once("../config/cors.php");
require_once("../config/conexion.php");
require_once("../controllers/RemisionController.php");

if (!isset($_GET['id_remision'])) { 
    echo json_encode(["success" => false, "message" => "Falta id_remision"]);
    exit;
}

// Usamos el nombre de la variable de GET que ya coincide con Flutter
$id_Remision = intval($_GET['id_remision']); 
$controller = new RemisionController();
$response = $controller->obtenerDetalleRemision($id_Remision);

echo json_encode($response);
?>