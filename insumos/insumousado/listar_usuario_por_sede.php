<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtener id_sede desde la URL
$id_sede = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : null;

if (!$id_sede) {
    echo json_encode([
        "success" => false,
        "message" => "Falta el parámetro id_sede."
    ]);
    exit;
}

try {
    // Consultar los usuarios que pertenecen a esa sede
    $query = "
        SELECT id, nombre, rol
        FROM usuarios
        WHERE id_sede = :id_sede
        ORDER BY nombre ASC
    ";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_sede', $id_sede);
    $stmt->execute();

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $usuarios
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener usuarios: " . $e->getMessage()
    ]);
}
?>
