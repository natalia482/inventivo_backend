<?php
// Archivo: inventivo_backend/sede/obtener_sede.php

require_once '../config/cors.php';
include_once '../config/conexion.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id_sede = isset($_GET['id_sede']) ? $_GET['id_sede'] : null;

if (empty($id_sede)) {
    echo json_encode(["success" => false, "message" => "Falta el ID de la sede."]);
    exit;
}

try {
    // Consulta para obtener datos de la sede y el nombre de la empresa
    $query = "SELECT 
                s.nombre_sede,
                s.direccion,
                s.telefonos,
                e.nombre_empresa
              FROM sedes s
              JOIN empresas e ON s.id_empresa = e.id
              WHERE s.id = :id_sede
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":id_sede", $id_sede, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $sede = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $sede]);
    } else {
        echo json_encode(["success" => false, "message" => "Sede no encontrada."]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de BD: " . $e->getMessage()]);
}
?>