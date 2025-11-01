<?php
require_once '../../../config/cors.php';
include_once '../../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents("php://input"));

// ✅ Auditoría: Necesitamos id_usuario_creador y id_sede
if (empty($data->id) || empty($data->id_usuario_creador) || empty($data->id_sede)) {
    echo json_encode(["success" => false, "message" => "Falta el ID del trabajador o IDs de auditoría."]);
    exit;
}

$id = $data->id;
$updateFields = [];
$params = [];

// ... (Preparamos los campos a actualizar: nombre, apellido, correo, password, etc.)

if (empty($updateFields)) {
    echo json_encode(["success" => false, "message" => "No hay campos para actualizar."]);
    exit;
}

// Armamos la consulta SQL dinámica
$query = "UPDATE usuarios SET " . implode(", ", $updateFields) . " WHERE id = :id";
$stmt = $db->prepare($query);
$params[':id'] = $id;

try {
    $db->beginTransaction();
    
    // 1. Ejecutar la actualización
    $stmt->execute($params);
    $rowsAffected = $stmt->rowCount();

    // 2. REGISTRAR AUDITORÍA
    if ($rowsAffected > 0) {
        $detalle_cambio = "Datos actualizados para usuario ID: " . $id;
        $queryAuditoria = "INSERT INTO auditoria_movimientos 
                           (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                           VALUES (:id_usuario, :id_sede, 'usuarios', :id_registro, 'ACTUALIZAR', :detalle)";
        
        $stmtAuditoria = $db->prepare($queryAuditoria);
        $stmtAuditoria->execute([
            ':id_usuario' => $data->id_usuario_creador, // ID del usuario que realizó la acción
            ':id_sede' => $data->id_sede,
            ':id_registro' => $id,
            ':detalle' => $detalle_cambio
        ]);
        
        $db->commit();
        echo json_encode(["success" => true, "message" => "Datos del trabajador actualizados y auditados."]);

    } else {
        $db->commit(); // Commit aunque no haya cambios (la acción se completó)
        echo json_encode(["success" => false, "message" => "No se encontró al trabajador o los datos eran iguales."]);
    }

} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error en la base de datos: " . $e->getMessage()]);
}
?>