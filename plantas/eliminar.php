<?php
require_once '.../config/cors.php';
include_once '../config/conexion.php';
include_once '../models/Plantas.php'; 

$database = new Database();
$db = $database->getConnection();
$planta = new Planta($db);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"] ?? null;
$id_sede = $data["id_sede"] ?? null; 
$id_usuario = $data["id_usuario"] ?? null; 

if (!$id || !$id_sede || !$id_usuario) {
    echo json_encode(["success" => false, "message" => "Falta el ID de la planta o datos de auditoría."]);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Eliminar la planta
    $resultado = $planta->eliminar($id); // Asumiendo que el modelo devuelve ['success' => true]

    if (!$resultado['success']) { 
         throw new Exception("No se pudo eliminar la planta.");
    }
    
    // 2. REGISTRAR AUDITORÍA
    $detalle_cambio = "Se eliminó permanentemente la planta ID: " . $id;
    $queryAuditoria = "INSERT INTO auditoria_movimientos 
                       (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                       VALUES (:id_usuario, :id_sede, 'plantas', :id_registro, 'ELIMINAR', :detalle)";
    
    $stmtAuditoria = $db->prepare($queryAuditoria);
    $stmtAuditoria->execute([
        ':id_usuario' => $id_usuario,
        ':id_sede' => $id_sede,
        ':id_registro' => $id,
        ':detalle' => $detalle_cambio
    ]);
    
    $db->commit();
    echo json_encode(["success" => true, "message" => "Planta eliminada y auditada."]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
}
?>