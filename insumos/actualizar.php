<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/conexion.php';
include_once '../models/insumo.php'; 

$database = new Database();
$db = $database->getConnection();
$insumo = new Insumo($db);

$data = $_POST; // Flutter envía la actualización como POST, no JSON

$id = isset($data['id']) ? trim($data['id']) : null;
$id_sede = isset($data['id_sede']) ? trim($data['id_sede']) : null;
$id_usuario = isset($data['id_usuario']) ? trim($data['id_usuario']) : null;

if (empty($id) || empty($id_sede) || empty($id_usuario)) {
    echo json_encode(["success" => false, "message" => "Faltan IDs de auditoría (id, id_sede, id_usuario)."]);
    exit;
}

// Asignar datos al modelo
$insumo->id = $id;
$insumo->id_sede = $id_sede;
$insumo->nombre_insumo = $data['nombre_insumo'] ?? '';
$insumo->categoria = $data['categoria'] ?? '';
$insumo->precio = $data['precio'] ?? 0;
$insumo->medida = $data['medida'] ?? '';
$insumo->cantidad = $data['cantidad'] ?? 0;
// (El estado se calcula dentro del modelo)

try {
    $db->beginTransaction();

    // 1. Actualizar el insumo
    if (!$insumo->actualizar()) { // Asumiendo que actualizar() está en el modelo
         throw new Exception("No se pudo actualizar el insumo o no se encontraron cambios.");
    }

    // 2. REGISTRAR AUDITORÍA
    $detalle_cambio = "Se actualizó el insumo: " . $insumo->nombre_insumo;
    $queryAuditoria = "INSERT INTO auditoria_movimientos 
                       (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                       VALUES (:id_usuario, :id_sede, 'insumos', :id_registro, 'ACTUALIZAR', :detalle)";
    
    $stmtAuditoria = $db->prepare($queryAuditoria);
    $stmtAuditoria->execute([
        ':id_usuario' => $id_usuario,
        ':id_sede' => $id_sede,
        ':id_registro' => $id,
        ':detalle' => $detalle_cambio
    ]);
    
    $db->commit();
    echo json_encode(["success" => true, "message" => "Insumo actualizado y auditado."]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
}
?>