<?php
require_once '../../config/cors.php';
require_once '../../config/conexion.php';
require_once '../../models/Usuario.php';
require_once '../../models/Empresa.php';
require_once '../../models/sede.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Datos del Propietario (Usuario)
    $nombre = $data['nombre'] ?? null;
    $apellido = $data['apellido'] ?? null;
    $correo = $data['correo'] ?? null;
    $password = $data['password'] ?? null;
    
    // Datos de la Empresa
    $nombre_empresa = $data['nombre_empresa'] ?? null;
    $nit = $data['nit'] ?? null;
    
    // Datos de la Sede (Usamos la dirección de la empresa)
    $direccion_sede = $data['direccion_empresa'] ?? null;
    $latitud = $data['latitud'] ?? null;
    $longitud = $data['longitud'] ?? null;


    if (!$nombre || !$apellido || !$correo || !$password || !$nombre_empresa || !$nit) {
        echo json_encode(["success" => false, "message" => "Faltan datos obligatorios."]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Habilitar excepciones

    $usuario = new Usuario($db);
    $empresa = new Empresa($db);
    $sede = new Sede($db);

    $db->beginTransaction();

    try {
        // 1. Crear la Empresa
        $empresa->nombre_empresa = $nombre_empresa;
        $empresa->nit = $nit;
        $empresa->direccion = $direccion_sede; // Asumimos que la dirección de la empresa es la de la sede principal
        $empresa->crear();
        $id_empresa = $empresa->id;

        // 2. Crear la Sede Principal
        $sede->id_empresa = $id_empresa;
        $sede->nombre_sede = "Sede Principal"; 
        $sede->direccion = $direccion_sede;
        $sede->latitud = $latitud;
        $sede->longitud = $longitud;
        $sede->crear();
        $id_sede = $sede->id;

        // 3. Crear el Usuario Propietario
        $usuario->nombre = $nombre;
        $usuario->apellido = $apellido;
        $usuario->correo = $correo;
        $usuario->password = password_hash($password, PASSWORD_BCRYPT);
        $usuario->rol = 'PROPIETARIO'; 
        $usuario->id_sede = $id_sede; 
        $usuario->crear();
        $id_usuario = $usuario->id;
        
        $db->commit();

        echo json_encode([
            "success" => true,
            "message" => "Propietario, empresa y sede principal creados exitosamente.",
            "id_usuario" => $id_usuario,
            "id_sede" => $id_sede,
            "id_empresa" => $id_empresa
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["success" => false, "message" => "Error en la transacción: " . $e->getMessage()]);
    }
}
?>