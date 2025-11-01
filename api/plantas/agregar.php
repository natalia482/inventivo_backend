<?php
require_once '../../config/cors.php';
require_once '../../config/conexion.php';
require_once '../../models/Plantas.php'; 

$database = new Database();
$db = $database->getConnection();
$planta = new Planta($db);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents("php://input"), true);

// Lectura de datos de auditoría
$id_sede = $data["id_sede"] ?? null; 
$id_usuario = $data["id_usuario"] ?? null; 
$nombre_plantas = $data["nombre_plantas"] ?? null;
$stock = $data["stock"] ?? null;

if (!$id_sede || !$id_usuario || !$nombre_plantas) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (incluyendo id_sede y id_usuario)."]);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Registrar la planta
    $resultado = $planta->agregar(
        $nombre_plantas,
        $data["numero_bolsa"] ?? '',
        $data["precio"] ?? 0,
        $data["categoria"] ?? '',
        $stock,
        $id_sede
    );
    
    if (!$resultado) { throw new Exception("Error al ejecutar el registro de planta."); }
    
    $id_planta_creada = $db->lastInsertId();

    // 2. REGISTRAR AUDITORÍA
    $detalle_cambio = "Planta agregada: " . $nombre_plantas . " (Stock inicial: " . $stock . ")";
    $queryAuditoria = "INSERT INTO auditoria_movimientos 
                       (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                       VALUES (:id_usuario, :id_sede, 'plantas', :id_registro, 'AGREGAR', :detalle)";
    
    $stmtAuditoria = $db->prepare($queryAuditoria);
    $stmtAuditoria->execute([
        ':id_usuario' => $id_usuario,
        ':id_sede' => $id_sede,
        ':id_registro' => $id_planta_creada,
        ':detalle' => $detalle_cambio
    ]);

    $db->commit();
    echo json_encode(["success" => true, "message" => "Planta registrada y auditada."]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
}
?>