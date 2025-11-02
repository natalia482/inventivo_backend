<?php
require_once "../../config/cors.php";
include_once "../../config/conexion.php";


if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // MODIFICADO: Aceptar id_sede
    if (!isset($_GET["id_sede"])) {
        echo json_encode(["status" => "error", "message" => "Falta el parámetro id_sede"]);
        exit;
    }

    $id_sede = $_GET["id_sede"];
    $filtro = isset($_GET["filtro"]) ? "%" . $_GET["filtro"] . "%" : null; 

    $database = new Database();
    $conexion = $database->getConnection(); 

    // MODIFICADO: Buscar por id_sede y excluir solo al Propietario
    $query = "SELECT id, nombre, apellido, correo, rol, estado, fecha_registro 
              FROM usuarios 
              WHERE rol != 'PROPIETARIO' AND id_sede = :id_sede";
    
    if ($filtro) {
        $query .= " AND (nombre LIKE :filtro OR apellido LIKE :filtro OR correo LIKE :filtro)";
    }
    
    $query .= " ORDER BY rol ASC, nombre ASC";

    $stmt = $conexion->prepare($query);
    $stmt->bindParam(":id_sede", $id_sede, PDO::PARAM_INT);
    
    if ($filtro) {
        $stmt->bindParam(":filtro", $filtro);
    }
    
    $stmt->execute();

    $trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $trabajadores]);
} else {
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
}
?>