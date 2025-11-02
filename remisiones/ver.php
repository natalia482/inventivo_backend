<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config/Conexion.php';
require_once '../controllers/RemisionController.php';

$db = (new Database())->getConnection();
$controller = new RemisionController($db);

if (isset($_GET['id'])) {
    $Remision = $controller->obtenerRemision($_GET['id']);
    echo json_encode($Remision);
} else {
    echo json_encode(["success" => false, "message" => "Falta el parámetro id"]);
}
?>
