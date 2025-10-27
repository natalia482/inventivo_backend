<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controllers/InsumoController.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->nombre_insumo) ||
    empty($data->categoria) ||
    !isset($data->precio) ||
    empty($data->medida) ||
    !isset($data->cantidad) ||
    empty($data->id_empresa)
) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios"]);
    exit;
}

//Verificar si ya existe un insumo con el mismo nombre para la misma empresa
$checkQuery = "SELECT id FROM insumos WHERE nombre_insumo = :nombre AND id_empresa = :id_empresa";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(":nombre", $data->nombre_insumo);
$checkStmt->bindParam(":id_empresa", $data->id_empresa);
$checkStmt->execute();

if ($checkStmt->rowCount() > 0) {
    echo json_encode(["success" => false, "message" => "El insumo ya existe para esta empresa."]);
    exit;
}

//Insertar el nuevo insumo
$query = "INSERT INTO insumos (nombre_insumo, categoria, precio, medida, cantidad, id_empresa, fecha_registro)
          VALUES (:nombre, :categoria, :precio, :medida, :cantidad, :id_empresa, NOW())";

$stmt = $db->prepare($query);
$stmt->bindParam(":nombre", $data->nombre_insumo);
$stmt->bindParam(":categoria", $data->categoria);
$stmt->bindParam(":precio", $data->precio);
$stmt->bindParam(":medida", $data->medida);
$stmt->bindParam(":cantidad", $data->cantidad);
$stmt->bindParam(":id_empresa", $data->id_empresa);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Insumo registrado correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al registrar el insumo."]);
}
?>