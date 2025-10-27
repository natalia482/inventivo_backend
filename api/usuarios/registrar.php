<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

require_once '../../config/conexion.php';
require_once '../../models/Usuario.php';
require_once '../../models/Empresa.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($data['nombre'], $data['apellido'], $data['correo'], 
        $data['password'], $data['nombre_empresa'], $data['nit'])
    ) {
        echo json_encode(["success" => false, "message" => "Faltan datos obligatorios."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $usuario = new Usuario($db);
    $empresa = new Empresa($db);

    $db->beginTransaction();

    try {
        // Crear el usuario administrador
        $usuario->nombre = $data['nombre'];
        $usuario->apellido = $data['apellido'];
        $usuario->correo = $data['correo'];
        $usuario->password = password_hash($data['password'], PASSWORD_BCRYPT);
        $usuario->rol = 'ADMINISTRADOR';
        $usuario->crear();

        $id_usuario = $db->lastInsertId();

        // Crear la empresa asociada
        $empresa->nombre_empresa = $data['nombre_empresa'];
        $empresa->nit = $data['nit'];
        $empresa->id_usuario = $id_usuario;
        $empresa->crear();

        $id_empresa = $db->lastInsertId();

        // 🔧 Actualizar el id_empresa en la tabla usuarios
        $query = "UPDATE usuarios SET id_empresa = :id_empresa WHERE id = :id_usuario";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_empresa', $id_empresa);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();

        $db->commit();

        echo json_encode([
            "success" => true,
            "message" => "Administrador y empresa creados exitosamente.",
            "id_usuario" => $id_usuario,
            "id_empresa" => $id_empresa
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}
?>
