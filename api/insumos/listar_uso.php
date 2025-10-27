<?php
// 🔹 Manejo completo de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si la petición es OPTIONS, solo respondemos los headers y salimos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json; charset=UTF-8");
include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

// Obtener el id de la empresa
$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null;

if (empty($id_empresa)) {
    echo json_encode(["success" => false, "message" => "Falta el id de la empresa."]);
    exit;
}

try {
    $query = "SELECT 
                u.id,
                u.fecha,
                i.nombre_insumo AS producto,
                u.cantidad_utilizada,
                u.dosificacion,
                u.objetivo,
                u.responsable,
                u.fecha_registro
              FROM actividades_agricolas u
              INNER JOIN insumos i ON u.id_insumo = i.id
              WHERE u.id_empresa = :id_empresa
              ORDER BY u.fecha DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":id_empresa", $id_empresa);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $registros = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $registros[] = $row;
        }

        echo json_encode([
            "success" => true,
            "data" => $registros
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "data" => [],
            "message" => "No hay movimientos registrados."
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error en el servidor: " . $e->getMessage()
    ]);
}
?>
