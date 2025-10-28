<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/conexion.php';
require_once '../../models/Plantas.php';  

// Crear instancia del modelo
$planta = new Planta();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["id"])) {
    echo json_encode(["success" => false, "message" => "Datos inválidos o falta el ID"]);
    exit;
}

$id = $data["id"];
$nombre = $data["nombre_plantas"] ?? '';
$numero_bolsa = $data["numero_bolsa"] ?? '';
$precio = $data["precio"] ?? 0;
$categoria = $data["categoria"] ?? '';
$stock = $data["stock"] ?? 0;
$estado = $data["estado"] ?? 'disponible';

// Ejecutar actualización
$resultado = $planta->actualizar($id, $nombre, $numero_bolsa, $precio, $categoria, $stock, $estado);

if ($resultado) {
    echo json_encode(["success" => true, "message" => "Planta actualizada correctamente"]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar la planta"]);
}
?>