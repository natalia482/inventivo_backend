<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

include_once '../../config/conexion.php'; 
include_once '../../controllers/FacturaController.php';

// Instanciar el controlador (él se encarga de la conexión)
$controller = new FacturaController(); 

// Obtener el ID de la empresa desde el parámetro GET (enviado desde Flutter)
$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null;

if ($id_empresa === null) {
    echo json_encode(["success" => false, "message" => "ID de empresa no especificado"]);
    exit;
}

// Llama al método del controlador, que internamente consulta las facturas filtradas por id_empresa.
$controller->listarFacturas($id_empresa);
?>