<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();

// Obtener datos JSON
$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->fecha) ||
    empty($data->id_insumo) ||
    empty($data->cantidad_utilizada) ||
    empty($data->dosificacion) ||
    empty($data->objetivo) ||
    empty($data->responsable) ||
    empty($data->id_empresa)
) {
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios."]);
    exit;
}

try {
    // Insertar registro de uso de insumo
    $query = "INSERT INTO actividades_agricolas 
              (fecha, id_insumo, cantidad_utilizada, dosificacion, objetivo, responsable, id_empresa, fecha_registro) 
              VALUES (:fecha, :id_insumo, :cantidad_utilizada, :dosificacion, :objetivo, :responsable, :id_empresa, NOW())";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':fecha', $data->fecha);
    $stmt->bindParam(':id_insumo', $data->id_insumo);
    $stmt->bindParam(':cantidad_utilizada', $data->cantidad_utilizada);
    $stmt->bindParam(':dosificacion', $data->dosificacion);
    $stmt->bindParam(':objetivo', $data->objetivo);
    $stmt->bindParam(':responsable', $data->responsable);
    $stmt->bindParam(':id_empresa', $data->id_empresa);

    if ($stmt->execute()) {
        // Descontar la cantidad utilizada del inventario del insumo
        $update = "UPDATE insumos 
                   SET cantidad = cantidad - :cantidad 
                   WHERE id = :id_insumo AND id_empresa = :id_empresa";
        $up = $db->prepare($update);
        $up->bindParam(':cantidad', $data->cantidad_utilizada);
        $up->bindParam(':id_insumo', $data->id_insumo);
        $up->bindParam(':id_empresa', $data->id_empresa);
        $up->execute();

        echo json_encode(["success" => true, "message" => "Uso de insumo registrado correctamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el uso de insumo."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error en el servidor: " . $e->getMessage()]);
}
?>
