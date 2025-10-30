<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/conexion.php';

// Capturar el mensaje desde Flutter
$data = json_decode(file_get_contents("php://input"), true);
$mensaje = strtolower(trim($data["mensaje"] ?? ""));

if ($mensaje == "") {
    echo json_encode(["respuesta" => "Por favor escribe el nombre de una planta 🌿."]);
    exit;
}

// Si el usuario saluda
if (in_array($mensaje, ["hola", "buenas", "hey", "saludos", "hi"])) {
    echo json_encode([
        "respuesta" => "¡Hola! 🌿 Soy el asistente de Inventivo.\nPuedes preguntarme por el nombre de una planta para saber si está disponible."
    ]);
    exit;
}

// Buscar la planta en la base de datos
try {
    $sql = "SELECT 
                p.nombre_plantas AS planta,
                p.categoria,
                p.stock,
                e.nombre_empresa AS vivero,
                p.estado
            FROM plantas p
            INNER JOIN empresas e ON p.id_empresa = e.id
            WHERE p.nombre_plantas LIKE :nombre
            ORDER BY p.stock DESC
            LIMIT 3";

    $stmt = $conn->prepare($sql);
    $busqueda = "%$mensaje%";
    $stmt->bindParam(":nombre", $busqueda, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $plantas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $respuesta = "🌿 Encontré lo siguiente:\n\n";

        foreach ($plantas as $planta) {
            if ($planta['stock'] > 0 && $planta['estado'] === 'disponible') {
                $respuesta .= "✔️ *{$planta['planta']}* ({$planta['categoria']})\n";
                $respuesta .= "   📍 Vivero: {$planta['vivero']}\n";
                $respuesta .= "   🌱 Stock: {$planta['stock']} unidades disponibles.\n\n";
            } else {
                $respuesta .= "❌ {$planta['planta']} no está disponible actualmente.\n\n";
            }
        }
    } else {
        $respuesta = "😔 No encontré ninguna planta que coincida con '$mensaje'.";
    }

    echo json_encode(["respuesta" => $respuesta]);

} catch (PDOException $e) {
    echo json_encode(["respuesta" => "Error en la consulta: " . $e->getMessage()]);
}
?>
