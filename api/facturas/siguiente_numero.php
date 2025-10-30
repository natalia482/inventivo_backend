<?php
// Fichero: inventivo_backend/.../api/facturas/siguiente_numero.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../../config/conexion.php';
require_once '../../controllers/facturaController.php';

$db = (new Database())->getConnection();
$controller = new FacturaController($db); // El constructor actualiza $this->db, así que pasarlo es seguro.

if (isset($_GET['id_empresa'])) {
    $id_empresa = intval($_GET['id_empresa']);
    $response = $controller->obtenerSiguienteNumeroFactura($id_empresa);
    echo json_encode($response);
} else {
    echo json_encode(["success" => false, "message" => "Falta el parámetro id_empresa"]);
}
?>