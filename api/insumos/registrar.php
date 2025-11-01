<?php
require '../../config/cors.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/insumo.php'; 

$database = new Database();
$db = $database->getConnection();
$insumo = new Insumo($db);

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->nombre_insumo) ||
    !isset($data->precio) ||
    !isset($data->cantidad) ||
    empty($data->id_sede) ||
    empty($data->id_usuario) // Requerido para auditoría
) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (incluyendo id_sede y id_usuario)."]);
    exit;
}

// Validación de duplicados (debe usar id_sede ahora)
$checkQuery = "SELECT id FROM insumos WHERE nombre_insumo = :nombre AND id_sede = :id_sede";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(":nombre", $data->nombre_insumo);
$checkStmt->bindParam(":id_sede", $data->id_sede);
$checkStmt->execute();

if ($checkStmt->rowCount() > 0) {
    echo json_encode(["success" => false, "message" => "El insumo ya existe para esta sede."]);
    exit;
}
// Asignar datos al modelo
$insumo->nombre_insumo = $data->nombre_insumo;
$insumo->categoria = $data->categoria ?? '';
$insumo->precio = $data->precio;
$insumo->medida = $data->medida ?? '';
$insumo->cantidad = $data->cantidad;
$insumo->id_sede = $data->id_sede; 

try {
    $db->beginTransaction();

    // 1. Registrar el insumo
    if (!$insumo->registrar()) { // Asumiendo que registrar() está en el modelo
        throw new Exception("Error al registrar el insumo.");
    }
    
    $id_insumo_creado = $db->lastInsertId();

    // 2. REGISTRAR AUDITORÍA
    $detalle_cambio = "Se creó el insumo: " . $insumo->nombre_insumo;
    $queryAuditoria = "INSERT INTO auditoria_movimientos 
                       (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                       VALUES (:id_usuario, :id_sede, 'insumos', :id_registro, 'AGREGAR', :detalle)";
    
    $stmtAuditoria = $db->prepare($queryAuditoria);
    $stmtAuditoria->execute([
        ':id_usuario' => $data->id_usuario,
        ':id_sede' => $data->id_sede,
        ':id_registro' => $id_insumo_creado,
        ':detalle' => $detalle_cambio
    ]);

    $db->commit();
    echo json_encode(["success" => true, "message" => "Insumo registrado y auditado."]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
}
?>