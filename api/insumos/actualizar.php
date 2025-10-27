<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

$data = $_POST;

$id = isset($data['id']) ? trim($data['id']) : null;
$nombre_insumo = isset($data['nombre_insumo']) ? trim($data['nombre_insumo']) : null;
$categoria = isset($data['categoria']) ? trim($data['categoria']) : null;
$medida = isset($data['medida']) ? trim($data['medida']) : null;
$precio = isset($data['precio']) ? trim($data['precio']) : null;
$cantidad = isset($data['cantidad']) ? trim($data['cantidad']) : null;

// Validar campos obligatorios
if (empty($id) || empty($nombre_insumo) || empty($categoria) || empty($medida) || empty($precio) || empty($cantidad)) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios."]);
    exit;
}

// Obtener la empresa a la que pertenece el insumo
$queryEmpresa = "SELECT id_empresa FROM insumos WHERE id = :id";
$stmtEmpresa = $db->prepare($queryEmpresa);
$stmtEmpresa->bindParam(":id", $id);
$stmtEmpresa->execute();

if ($stmtEmpresa->rowCount() == 0) {
    echo json_encode(["success" => false, "message" => "No se encontró el insumo especificado."]);
    exit;
}

$empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);
$id_empresa = $empresa['id_empresa'];

// ✅ Validar si ya existe otro insumo con el mismo nombre en la misma empresa
$checkQuery = "SELECT id FROM insumos 
               WHERE nombre_insumo = :nombre_insumo 
               AND id_empresa = :id_empresa 
               AND id != :id";

$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(":nombre_insumo", $nombre_insumo);
$checkStmt->bindParam(":id_empresa", $id_empresa);
$checkStmt->bindParam(":id", $id);
$checkStmt->execute();

if ($checkStmt->rowCount() > 0) {
    echo json_encode(["success" => false, "message" => "Ya existe otro insumo con ese nombre en la empresa."]);
    exit;
}

// ✅ Actualizar insumo
$query = "UPDATE insumos 
          SET nombre_insumo = :nombre_insumo, categoria = :categoria, medida = :medida, 
              precio = :precio, cantidad = :cantidad 
          WHERE id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(":nombre_insumo", $nombre_insumo);
$stmt->bindParam(":categoria", $categoria);
$stmt->bindParam(":medida", $medida);
$stmt->bindParam(":precio", $precio);
$stmt->bindParam(":cantidad", $cantidad);
$stmt->bindParam(":id", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Insumo actualizado correctamente."]);
} else {
    echo json_encode(["success" => false, "message" => "Error al actualizar el insumo."]);
}
?>
