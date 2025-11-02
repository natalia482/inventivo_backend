<?php
require_once '../config/cors.php';
require_once '../config/conexion.php';
require_once '../models/Plantas.php';  

$database = new Database();
$db = $database->getConnection();
$planta = new Planta($db);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents("php://input"), true);

// Datos de la planta
$id = $data["id"] ?? null;
$nombre = $data["nombre_plantas"] ?? '';
$numero_bolsa = $data["numero_bolsa"] ?? '';
$precio = $data["precio"] ?? 0;
$categoria = $data["categoria"] ?? '';
$stock = $data["stock"] ?? 0;
$estado = $data["estado"] ?? 'disponible';
// Datos de auditoría
$id_sede = $data["id_sede"] ?? null; 
$id_usuario = $data["id_usuario"] ?? null; 

if (!$id || !$id_sede || !$id_usuario) {
    echo json_encode(["success" => false, "message" => "Falta el ID de la planta o datos de auditoría."]);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Actualizar la planta
    $resultado = $planta->actualizar(
        $id,
        $nombre,
        $numero_bolsa,
        $precio,
        $categoria,
        $stock,
        $estado,
        $id_sede // Argumento esperado por el modelo
    );

    if (!$resultado) { 
         throw new Exception("No se pudo actualizar la planta o no se encontraron cambios.");
    }

    // 2. REGISTRAR AUDITORÍA
    $detalle_cambio = "Planta actualizada: " . $nombre . " (Stock nuevo: " . $stock . ")";
    $queryAuditoria = "INSERT INTO auditoria_movimientos 
                       (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                       VALUES (:id_usuario, :id_sede, 'plantas', :id_registro, 'ACTUALIZAR', :detalle)";
    
    $stmtAuditoria = $db->prepare($queryAuditoria);
    $stmtAuditoria->execute([
        ':id_usuario' => $id_usuario,
        ':id_sede' => $id_sede,
        ':id_registro' => $id,
        ':detalle' => $detalle_cambio
    ]);
    
    $db->commit();
    echo json_encode(["success" => true, "message" => "Planta actualizada y auditada."]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
}
?>