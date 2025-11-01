<?php
require_once '../../../config/cors.php';
include_once '../../../config/conexion.php';
include_once '../../../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

$data = json_decode(file_get_contents("php://input"));

// Verificación de datos (espera id_sede, rol y id_usuario del creador)
if (
    empty($data->nombre) ||
    empty($data->correo) ||
    empty($data->password) ||
    empty($data->id_sede) || 
    empty($data->rol) ||
    empty($data->id_usuario_creador) // ✅ Requerido para Auditoría
) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (id_sede, rol y id_usuario_creador)."]);
    exit;
}

if ($data->rol == 'PROPIETARIO') {
     echo json_encode(["success" => false, "message" => "No se puede crear un rol 'PROPIETARIO' desde esta API."]);
    exit;
}

$usuario = new Usuario($db);
$usuario->nombre = $data->nombre;
$usuario->apellido = $data->apellido;
$usuario->correo = $data->correo;
$usuario->password = password_hash($data->password, PASSWORD_BCRYPT);
$usuario->rol = $data->rol; 
$usuario->id_sede = $data->id_sede; 

try {
    $db->beginTransaction();
    
    // 1. Crear el usuario
    if (!$usuario->crear()) {
        throw new Exception("Error al registrar el usuario en la tabla.");
    }
    $id_usuario_creado = $usuario->id;

    // 2. REGISTRAR AUDITORÍA
    $detalle_cambio = "Usuario registrado (" . $data->rol . "): " . $data->nombre . " " . $data->apellido;
    $queryAuditoria = "INSERT INTO auditoria_movimientos 
                       (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                       VALUES (:id_usuario, :id_sede, 'usuarios', :id_registro, 'AGREGAR', :detalle)";
    
    $stmtAuditoria = $db->prepare($queryAuditoria);
    $stmtAuditoria->execute([
        ':id_usuario' => $data->id_usuario_creador, // ID del usuario que realizó la acción
        ':id_sede' => $data->id_sede,
        ':id_registro' => $id_usuario_creado,
        ':detalle' => $detalle_cambio
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "Usuario (" . $data->rol . ") registrado y auditado correctamente.",
        "id_usuario" => $id_usuario_creado,
        "id_sede" => $data->id_sede
    ]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
}
?>