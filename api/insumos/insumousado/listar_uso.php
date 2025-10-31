<?php
require_once '../../../config/cors.php';
include_once '../../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

$id_empresa = null;

// 1. Intentar obtener el ID desde la URL (Método GET)
if (isset($_GET['id_empresa'])) {
    $id_empresa = $_GET['id_empresa'];
} 
// 2. Si no se encuentra, intentar leer del cuerpo JSON (Método POST)
else {
    $data = json_decode(file_get_contents("php://input"));
    if (isset($data->id_empresa)) {
        $id_empresa = $data->id_empresa;
    }
}

if (empty($id_empresa)) {
    echo json_encode(["success" => false, "message" => "Falta el ID de la empresa para listar actividades."]);
    exit;
}

// Consulta principal con filtro de empresa
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
          WHERE aa.id_empresa = :id_empresa  
          ORDER BY aa.fecha DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":id_empresa", $id_empresa);
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
    echo json_encode(["success" => true, "data" => [], "message" => "No se encontraron actividades agrícolas."]);
}
?>