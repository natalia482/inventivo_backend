<?php
require_once '../../config/cors.php';
require_once '../../config/conexion.php'; 
require_once '../../controllers/remisionController.php'; // Modificado

$controller = new RemisionController(); 

$id_sede = isset($_GET['id_sede']) ? $_GET['id_sede'] : null; // Modificado

if ($id_sede === null) {
    echo json_encode(["success" => false, "message" => "ID de sede no especificado"]);
    exit;
}

$controller->listarRemisions($id_sede); // El método en el controller se mantiene
?>