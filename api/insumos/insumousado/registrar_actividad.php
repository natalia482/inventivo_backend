<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

include_once '../../../config/conexion.php';
$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

$id_insumo = $data['id_insumo'] ?? null;
$cantidad_utilizada = $data['cantidad_utilizada'] ?? null;
$dosificacion = $data['dosificacion'] ?? null;
$objetivo = $data['objetivo'] ?? null;
$responsable = $data['responsable'] ?? null;
$id_empresa = $data['id_empresa'] ?? null;

if (!$id_insumo || !$cantidad_utilizada || !$id_empresa) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios"]);
    exit;
}

try {
    $db->beginTransaction();

    // Registrar la actividad agrícola
    $queryActividad = "INSERT INTO actividades_agricolas (id_insumo, cantidad_utilizada, dosificacion, objetivo, responsable, id_empresa, fecha)
                       VALUES (:id_insumo, :cantidad_utilizada, :dosificacion, :objetivo, :responsable, :id_empresa, NOW())";
    $stmt = $db->prepare($queryActividad);
    $stmt->execute([
        ':id_insumo' => $id_insumo,
        ':cantidad_utilizada' => $cantidad_utilizada,
        ':dosificacion' => $dosificacion,
        ':objetivo' => $objetivo,
        ':responsable' => $responsable,
        ':id_empresa' => $id_empresa
    ]);

    // Verificar stock actual
    $checkStock = $db->prepare("SELECT cantidad FROM insumos WHERE id = :id_insumo");
    $checkStock->execute([':id_insumo' => $id_insumo]);
    $stockActual = $checkStock->fetchColumn();

    if ($stockActual < $cantidad_utilizada) {
        echo json_encode(["success" => false, "message" => "Insumo insuficiente para registrar esta actividad."]);
        $db->rollBack();
        exit;
    }


    //  Actualizar el stock del insumo (restar lo usado)
    $queryStock = "UPDATE insumos 
                   SET cantidad = cantidad - :cantidad_utilizada 
                   WHERE id = :id_insumo AND id_empresa = :id_empresa";
    $stmtStock = $db->prepare($queryStock);
    $stmtStock->execute([
        ':cantidad_utilizada' => $cantidad_utilizada,
        ':id_insumo' => $id_insumo,
        ':id_empresa' => $id_empresa
    ]);

    // Confirmar transacción
    $db->commit();

    echo json_encode(["success" => true, "message" => "Actividad registrada y stock actualizado correctamente"]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error al registrar actividad: " . $e->getMessage()]);
}
?>
