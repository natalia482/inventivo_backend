<?php
require_once "../../..config/cors.php";
include_once "../../config/conexion.php";

$database = new Database();
$conexion = $database->getConnection();
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Habilitar excepciones

$id_sede = null;

// Leemos el id_sede (ya sea por GET o POST JSON)
if (isset($_GET["id_sede"])) {
    $id_sede = $_GET["id_sede"];
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    $id_sede = $data["id_sede"] ?? null;
}

if (empty($id_sede)) {
    echo json_encode(["success" => false, "message" => "Falta el parámetro id_sede"]);
    exit;
}

try {
    // ✅ CORRECCIÓN: Cambiamos "id_empresa" por "id_sede" en la consulta WHERE
    $query = "SELECT id, nombre, apellido, correo, rol 
              FROM usuarios 
              WHERE id_sede = :id_sede AND estado = 'ACTIVO'
              ORDER BY rol DESC, nombre ASC"; 

    $stmt = $conexion->prepare($query);
    $stmt->bindParam(":id_sede", $id_sede, PDO::PARAM_INT); // Enlazar el id_sede
    $stmt->execute();

    $personal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear el nombre para la lista desplegable de Flutter
    $personal_formateado = array_map(function($user) {
        return [
            'id' => $user['id'],
            'nombre_completo' => $user['nombre'] . ' ' . $user['apellido'],
            'display' => $user['nombre'] . ' ' . $user['apellido'] . ' (' . $user['rol'] . ')'
        ];
    }, $personal);


    echo json_encode(["success" => true, "data" => $personal_formateado]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
}
?>