<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

include_once '../config/conexion.php';
include_once '../models/insumo.php'; // Incluir modelo

$database = new Database();
$db = $database->getConnection();
$insumo = new Insumo($db);

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id) || empty($data->id_sede) || empty($data->id_usuario)) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (id, id_sede, id_usuario)."]);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Eliminar el insumo
    if (!$insumo->eliminar($data->id)) { // Asumiendo que eliminar() está en el modelo
         throw new Exception("No se pudo eliminar el insumo.");
    }

    // 2. REGISTRAR AUDITORÍA
    $detalle_cambio = "Se eliminó permanentemente el insumo ID: " . $data->id;
    $queryAuditoria = "INSERT INTO auditoria_movimientos 
                       (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                       VALUES (:id_usuario, :id_sede, 'insumos', :id_registro, 'ELIMINAR', :detalle)";
    
    $stmtAuditoria = $db->prepare($queryAuditoria);
    $stmtAuditoria->execute([
        ':id_usuario' => $data->id_usuario,
        ':id_sede' => $data->id_sede,
        ':id_registro' => $data->id,
        ':detalle' => $detalle_cambio
    ]);
    
    $db->commit();
    echo json_encode(["success" => true, "message" => "Insumo eliminado y auditado."]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
}
?>