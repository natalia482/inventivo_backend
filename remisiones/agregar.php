<?php
require_once '../../config/cors.php';
require_once '../../config/conexion.php'; 
require_once '../../controllers/remisionController.php'; // Modificado
    
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}


$data = json_decode(file_get_contents("php://input"), true);
$controller = new RemisionController();
$controller->agregarRemision($data);
?>