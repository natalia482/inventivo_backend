<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once '../../config/conexion.php'; // ajusta la ruta según tu estructura

$database = new Database();
$db = $database->getConnection();

// Obtén el ID del usuario desde el parámetro GET
$id_usuario = isset($_GET['id_usuario']) ? $_GET['id_usuario'] : null;

if ($id_usuario === null) {
    echo json_encode(["success" => false, "message" => "ID de usuario no especificado"]);
    exit;
}

try {
    // Consulta las facturas del usuario logueado
    $query = "SELECT * FROM facturas WHERE id_usuario = :id_usuario";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id_usuario", $id_usuario);
    $stmt->execute();

    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $facturas]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error al obtener facturas: " . $e->getMessage()]);
}
?>
