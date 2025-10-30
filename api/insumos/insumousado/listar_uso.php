<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

// Consulta principal
$query = "SELECT 
            aa.id,
            aa.fecha,
            i.nombre_insumo,
            aa.cantidad_utilizada,
            aa.dosificacion,
            aa.objetivo,
            aa.responsable
          FROM actividades_agricolas aa
          INNER JOIN insumos i ON aa.id_insumo = i.id
          ORDER BY aa.fecha DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$actividades = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $actividades[] = [
        "id" => $row['id'],
        "fecha" => $row['fecha'],
        "nombre_insumo" => $row['nombre_insumo'],
        "cantidad_utilizada" => $row['cantidad_utilizada'],
        "dosificacion" => $row['dosificacion'],
        "objetivo" => $row['objetivo'],
        "responsable" => $row['responsable']
    ];
}

if (count($actividades) > 0) {
    echo json_encode(["success" => true, "data" => $actividades]);
} else {
    echo json_encode(["success" => false, "message" => "No se encontraron actividades agrícolas."]);
}
?>
