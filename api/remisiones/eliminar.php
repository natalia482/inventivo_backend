<?php
require_once '../../controllers/RemisionController.php';
require_once '../../config/cors.php';

// Intenta leer el ID del cuerpo JSON (usado por Flutter http.post)
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null; 

// Fallback para leer de GET, aunque Flutter usa JSON
if (empty($id)) {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
}

if (empty($id)) {
    // Retornamos un error de Remision, no de PLANTA.
    echo json_encode(["success" => false, "message" => "Falta el ID de la Remision a eliminar."]);
    exit;
}

$controller = new RemisionController();
// El controlador se encarga de la lógica de negocio: devolver stock y eliminar.
$controller->eliminarRemision($id);
?>