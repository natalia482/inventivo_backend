<?php
require '../../config/cors.php';
include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Obtener parámetros requeridos (asumimos que el chatbot envía el nombre y el ID de la empresa dueña)
$plant_name = isset($_GET['nombre']) ? trim($_GET['nombre']) : null;
$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null; 

if (empty($plant_name) || empty($id_empresa)) {
    echo json_encode([
        "success" => false, 
        "message" => "Faltan parámetros: nombre de la planta o ID de la empresa."
    ]);
    exit;
}

try {
    // 2. Consulta que une Plantas con Sedes y Empresas, filtrando por nombre y stock > 0
    $query = "
        SELECT 
            p.nombre_plantas, 
            p.stock,
            s.nombre_sede,
            s.direccion
        FROM plantas p
        JOIN sedes s ON p.id_sede = s.id
        JOIN empresas e ON s.id_empresa = e.id
        WHERE e.id = :id_empresa 
        AND p.stock > 0
        AND p.nombre_plantas LIKE :plant_name
        ORDER BY s.nombre_sede ASC";

    $stmt = $db->prepare($query);
    $plant_name_like = "%" . $plant_name . "%";
    
    $stmt->bindParam(":id_empresa", $id_empresa, PDO::PARAM_INT);
    $stmt->bindParam(":plant_name", $plant_name_like);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($resultados) > 0) {
        // 3. Formatear la respuesta (para el consumo del chatbot)
        $respuesta_formateada = [];
        $respuesta_texto = "¡Excelente! Encontramos la planta '{$plant_name}' en las siguientes sedes:\n\n";

        foreach ($resultados as $row) {
            $stock = (int)$row['stock'];
            $direccion = $row['direccion'] ?? 'Dirección no especificada';

            $respuesta_formateada[] = [
                'nombre' => $row['nombre_plantas'],
                'stock' => $stock,
                'sede' => $row['nombre_sede'],
                'direccion' => $direccion
            ];
            
            $respuesta_texto .= "- Sede: {$row['nombre_sede']} (Stock: {$stock})\n";
            $respuesta_texto .= "  Ubicación: {$direccion}\n\n";
        }
        
        echo json_encode([
            "success" => true,
            "message" => $respuesta_texto, // Mensaje listo para ser mostrado por el chatbot
            "results" => $respuesta_formateada
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Lo siento, no encontramos la planta '{$plant_name}' disponible en ninguna sede.",
            "results" => []
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
}
?>