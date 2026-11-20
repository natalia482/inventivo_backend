<?php
require_once '../../config/cors.php';
include_once "../../config/conexion.php";

$database = new Database();
$conexion = $database->getConnection();
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    // ✅ Auditoría: Necesitamos id_usuario_creador y id_sede
    if (!isset($data["id"]) || !isset($data["id_usuario_creador"]) || !isset($data["id_sede"])) {
        echo json_encode(["success" => false, "message" => "Falta el ID del trabajador o IDs de auditoría."]);
        exit;
    }

    $id = $data["id"];
    
    // Consulta para obtener el rol del usuario a eliminar antes de borrarlo
    $queryRol = "SELECT rol FROM usuarios WHERE id = :id";
    $stmtRol = $conexion->prepare($queryRol);
    $stmtRol->bindParam(":id", $id);
    $stmtRol->execute();
    $rolEliminado = $stmtRol->fetchColumn();


    try {
        $conexion->beginTransaction();

        // 1. Ejecutar la eliminación
        // Se eliminó la restricción "AND rol = 'TRABAJADOR'" en pasos anteriores para permitir Admin/Prop eliminar Admin/Trab
        $query = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $rowsAffected = $stmt->rowCount();


        // 2. REGISTRAR AUDITORÍA
        if ($rowsAffected > 0) {
            $detalle_cambio = "Eliminación del usuario ID: " . $id . " (Rol: " . $rolEliminado . ")";
            $queryAuditoria = "INSERT INTO auditoria_movimientos 
                               (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                               VALUES (:id_usuario, :id_sede, 'usuarios', :id_registro, 'ELIMINAR', :detalle)";
            
            $stmtAuditoria = $conexion->prepare($queryAuditoria);
            $stmtAuditoria->execute([
                ':id_usuario' => $data['id_usuario_creador'], // ID del usuario que realizó la acción
                ':id_sede' => $data['id_sede'],
                ':id_registro' => $id,
                ':detalle' => $detalle_cambio
            ]);

            $conexion->commit();
            echo json_encode(["success" => true, "message" => "Usuario eliminado y auditado correctamente"]);

        } else {
            $conexion->commit(); // No se eliminó, pero la transacción finaliza sin error
            echo json_encode(["success" => false, "message" => "No se encontró el usuario a eliminar."]);
        }
    } catch (PDOException $e) {
        $conexion->rollBack();
        echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
    }
}
?>