<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

include_once '../../../config/conexion.php';
$database = new Database();
$db = $database->getConnection();

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
    $actividades[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $actividades
]);
?>
