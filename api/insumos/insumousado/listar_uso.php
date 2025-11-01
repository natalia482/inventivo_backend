<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id_sede = null;

$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['id_sede'])) {
    $id_sede = $data['id_sede'];
} elseif (isset($_GET['id_sede'])) {
    $id_sede = $_GET['id_sede'];
}

if (empty($id_sede)) {
    echo json_encode(["success" => false, "message" => "Falta el ID de la sede."]);
    exit;
}

try {
    $query = "SELECT 
                aa.id,
                aa.fecha,
                i.nombre_insumo,
                aa.cantidad_utilizada, 
                aa.objetivo,
                aa.responsable
              FROM actividades_agricolas aa
              INNER JOIN insumos i ON aa.id_insumo = i.id
              WHERE aa.id_sede = :id_sede
              ORDER BY aa.fecha DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":id_sede", $id_sede, PDO::PARAM_INT);
    $stmt->execute();

    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $actividades]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
}
?>
