<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
include_once "../../../config/conexion.php";


if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (!isset($_GET["id_empresa"])) {
        echo json_encode(["status" => "error", "message" => "Falta el parámetro id_empresa"]);
        exit;
    }

    $id_empresa = $_GET["id_empresa"];

    //  Crear instancia de la clase Database
    $database = new Database();
    $conexion = $database->getConnection(); // <-- aquí se obtiene el PDO

    // Usar consulta preparada con PDO
    $query = "SELECT id, nombre, apellido, correo, rol, estado, fecha_registro 
          FROM usuarios 
          WHERE rol = 'TRABAJADOR' AND id_empresa = :id_empresa";

    $stmt = $conexion->prepare($query);
    $stmt->bindParam(":id_empresa", $id_empresa, PDO::PARAM_INT);
    $stmt->execute();

    $trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $trabajadores]);
} else {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
}
?>
