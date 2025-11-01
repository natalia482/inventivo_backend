<?php
require_once '../../controllers/RemisionController.php';
require_once '../../config/cors.php';

$id = isset($_GET['id']) ? $_GET['id'] : null; 

if (empty($id)) {
    // Retornamos un error de Remision, no de PLANTA.
    echo json_encode(["success" => false, "message" => "Falta el ID de la Remision a eliminar."]);
    exit;
}

$controller = new RemisionController();
$controller->eliminarRemision($id);
?>