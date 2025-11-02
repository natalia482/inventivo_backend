<?php
// Archivo: inventivo_backend/usuarios/restablecer_contrasena.php
require '../config/cors.php';
require '../config/conexion.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;
$newPassword = $data['password'] ?? null;

if (empty($token) || empty($newPassword)) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios (token o nueva contraseña)."]);
    exit;
}

// 1. Buscar usuario por token y verificar expiración
$queryUser = "SELECT id FROM usuarios 
              WHERE reset_token = :token 
              AND token_expires_at > NOW() 
              LIMIT 1";
$stmtUser = $db->prepare($queryUser);
$stmtUser->bindParam(':token', $token);
$stmtUser->execute();
$usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(["success" => false, "message" => "El enlace es inválido o ha expirado. Solicita una nueva recuperación."]);
    exit;
}

$idUsuario = $usuario['id'];
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

try {
    $db->beginTransaction();

    // 2. Actualizar la contraseña
    $queryUpdatePass = "UPDATE usuarios 
                        SET password = :password, reset_token = NULL, token_expires_at = NULL 
                        WHERE id = :id";
    $stmtUpdatePass = $db->prepare($queryUpdatePass);
    $stmtUpdatePass->execute([
        ':password' => $hashedPassword,
        ':id' => $idUsuario
    ]);

    // 3. Registrar auditoría (opcional pero recomendado)
    $detalle_cambio = "Contraseña restablecida exitosamente vía token de recuperación.";
    $queryAuditoria = "INSERT INTO auditoria_movimientos (id_usuario, tabla_afectada, tipo_operacion, detalle_cambio)
                       VALUES (:id_usuario, 'usuarios', 'ACTUALIZAR', :detalle)";
    // NOTA: Para la auditoría, necesitarás el id_sede, el cual ya no está disponible si el usuario no está logueado. 
    // Por simplicidad, omitimos id_sede, pero se recomienda guardar un valor por defecto (ej. 0 o NULL).
    
    // Si la tabla auditoria_movimientos requiere id_sede, usa 0 o un valor seguro:
    // $idSedeDummy = 0; 
    // $queryAuditoria = "... id_sede, ..." VALUES (..., :id_sede, ...)
    
    $db->commit();

    echo json_encode(["success" => true, "message" => "Contraseña restablecida correctamente."]);

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
}
?>