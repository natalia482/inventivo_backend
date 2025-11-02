<?php
require '../config/cors.php';
include_once '../config/conexion.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Solo necesitamos el nombre de la planta
$plant_name = isset($_GET['nombre']) ? trim($_GET['nombre']) : null;
// El id_empresa se ignora para la búsqueda pública, pero mantenemos la recepción por si acaso.
$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null; 

if (empty($plant_name)) {
    echo json_encode([
        "success" => false, 
        "message" => "Faltan parámetros: nombre de la planta."
    ]);
    exit;
}

try {
    // ✅ CORRECCIÓN: Agregamos la selección de e.nombre_empresa
    // y mantenemos el JOIN a la tabla de empresas.
    $query = "
        SELECT 
            p.nombre_plantas, 
            p.stock,
            s.nombre_sede,
            s.direccion,
            e.nombre_empresa  -- <-- CAMBIO: Nombre de la Empresa
        FROM plantas p
        JOIN sedes s ON p.id_sede = s.id
        JOIN empresas e ON s.id_empresa = e.id 
        WHERE p.stock > 0
        AND p.nombre_plantas LIKE :plant_name
        ORDER BY e.nombre_empresa ASC, s.nombre_sede ASC";

    $stmt = $db->prepare($query);
    $plant_name_like = "%" . $plant_name . "%";
    
    // Solo enlazamos el parámetro del nombre
    $stmt->bindParam(":plant_name", $plant_name_like);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($resultados) > 0) {
        
        $respuesta_formateada = [];
        $respuesta_texto = "¡Excelente! Encontramos la planta '{$plant_name}' disponible en:\n\n"; // Título actualizado

        foreach ($resultados as $row) {
            $stock = (int)$row['stock'];
            $direccion = $row['direccion'] ?? 'Dirección no especificada';
            $nombre_empresa = $row['nombre_empresa'] ?? 'Empresa desconocida'; // Nuevo campo
            
            // Estructura de datos para Flutter (si la necesitas)
            $respuesta_formateada[] = [
                'nombre' => $row['nombre_plantas'],
                'stock' => $stock,
                'sede' => $row['nombre_sede'],
                'direccion' => $direccion,
                'empresa' => $nombre_empresa  // <-- Nuevo campo
            ];
            
            // ✅ CORRECCIÓN: Estructura de texto conversacional para el cliente
            $respuesta_texto .= "🟢 Empresa: {$nombre_empresa}\n";
            $respuesta_texto .= "   - Sede: {$row['nombre_sede']}\n";
            $respuesta_texto .= "   - Stock: {$stock}\n";
            $respuesta_texto .= "   - Ubicación: {$direccion}\n\n";
        }
        
        echo json_encode([
            "success" => true,
            "message" => $respuesta_texto, 
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