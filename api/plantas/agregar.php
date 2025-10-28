<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/conexion.php';
include_once '../../controllers/PlantasController.php';

$database = new Database();
$db = $database->getConnection();

$controller = new PlantaController($db);
$controller->agregar();
?>
