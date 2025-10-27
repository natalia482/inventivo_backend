<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

// Obtener el ID de empresa (viene por GET o JSON)
$id_empresa = null;

if (isset($_GET['id_empresa'])) {
    $id_empresa = $_GET['id_empresa'];
} else {
    $data = json_decode(file_get_contents("php://input"));
    if (isset($data->id_empresa)) {
        $id_empresa = $data->id_empresa;
    }
}

if (empty($id_empresa)) {
    echo json_encode(["success" => false, "message" => "Falta el id de la empresa."]);
    exit;
}

// ✅ Consultar solo los insumos de la empresa logueada
$query = "SELECT id, nombre_insumo, categoria, precio, medida, cantidad, fecha_registro
          FROM insumos
          WHERE id_empresa = :id_empresa
          ORDER BY fecha_registro DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":id_empresa", $id_empresa);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $insumos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $insumos[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $insumos
    ]);
} else {
    echo json_encode([
        "success" => true,
        "data" => [],
        "message" => "No hay insumos registrados para esta empresa."
    ]);
}
?>
