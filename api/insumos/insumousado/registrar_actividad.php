<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
include_once '../../../config/conexion.php';
$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

$id_insumo = $data["id_insumo"] ?? null;
$cantidad_utilizada = $data["cantidad_utilizada"] ?? null;
$dosificacion = $data["dosificacion"] ?? "";
$objetivo = $data["objetivo"] ?? "";
$responsable = $data["responsable"] ?? "";
$id_empresa = $data["id_empresa"] ?? null;

if (!$id_insumo || !$cantidad_utilizada) {
    echo json_encode(["success" => false, "message" => "Faltan campos obligatorios"]);
    exit;
}

try {
    $db->beginTransaction();

    // Obtener cantidad actual y medida
    $stmt = $db->prepare("SELECT cantidad, medida FROM insumos WHERE id = ?");
    $stmt->execute([$id_insumo]);
    $insumo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$insumo) {
        echo json_encode(["success" => false, "message" => "Insumo no encontrado"]);
        exit;
    }

    $cantidadActual = floatval($insumo["cantidad"]);
    $cantidadUsada = floatval($cantidad_utilizada);

    if ($cantidadUsada > $cantidadActual) {
        echo json_encode(["success" => false, "message" => "Cantidad utilizada mayor a la disponible"]);
        exit;
    }

    // Actualizar cantidad disponible
    $nuevaCantidad = $cantidadActual - $cantidadUsada;
    $update = $db->prepare("UPDATE insumos SET cantidad = ? WHERE id = ?");
    $update->execute([$nuevaCantidad, $id_insumo]);

    // Registrar actividad
    $insert = $db->prepare("INSERT INTO actividades_agricolas
        (fecha, id_insumo, cantidad_utilizada, dosificacion, objetivo, responsable, id_empresa, fecha_registro)
        VALUES (NOW(), ?, ?, ?, ?, ?, ?, NOW())");
    $insert->execute([$id_insumo, $cantidadUsada, $dosificacion, $objetivo, $responsable, $id_empresa]);

    $db->commit();

    echo json_encode(["success" => true, "message" => "Actividad registrada correctamente"]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
