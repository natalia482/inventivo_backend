<?php
require '../config/cors.php';
include_once '../config/conexion.php';
include_once '../config/config_openai.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtenemos el texto ingresado por el usuario
$user_message = isset($_GET['nombre']) ? trim($_GET['nombre']) : null;
$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null; 

if (empty($user_message)) {
    echo json_encode(["success" => false, "message" => "Faltan parámetros: mensaje del usuario."]);
    exit;
}

// --- Paso 1: Usar la IA para identificar el nombre de la planta en el texto ---
$OPENAI_API_KEY = "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"; // 🔒 reemplázala por tu clave real

$prompt_extract = "Del siguiente mensaje de usuario, identifica únicamente el nombre de la planta o especie mencionada. 
Si no hay ninguna planta clara, responde solo con la palabra 'ninguna'. 
Mensaje: '{$user_message}'";

$ch_extract = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch_extract, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_extract, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $OPENAI_API_KEY
]);
$data_extract = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role" => "system", "content" => "Eres un asistente que solo devuelve el nombre de la planta mencionada."],
        ["role" => "user", "content" => $prompt_extract]
    ],
    "max_tokens" => 20,
    "temperature" => 0.0
];
curl_setopt($ch_extract, CURLOPT_POST, true);
curl_setopt($ch_extract, CURLOPT_POSTFIELDS, json_encode($data_extract));

$response_extract = curl_exec($ch_extract);
curl_close($ch_extract);

$ai_extract = json_decode($response_extract, true);
$plant_name = strtolower(trim($ai_extract['choices'][0]['message']['content'] ?? ''));

// Validamos si encontró algo
if ($plant_name === '' || $plant_name === 'ninguna') {
    echo json_encode([
        "success" => false,
        "message" => "No se detectó ninguna planta en el mensaje. Intenta escribir, por ejemplo: 'Quiero saber sobre la rosa'."
    ]);
    exit;
}

// --- Paso 2: Buscar la planta detectada en la base de datos ---
try {
    $query = "
        SELECT 
            p.nombre_plantas, 
            p.stock,
            s.nombre_sede,
            s.direccion,
            s.telefonos,
            e.nombre_empresa
        FROM plantas p
        JOIN sedes s ON p.id_sede = s.id
        JOIN empresas e ON s.id_empresa = e.id 
        WHERE p.stock > 0
        AND p.nombre_plantas LIKE :plant_name
        ORDER BY e.nombre_empresa ASC, s.nombre_sede ASC";

    $stmt = $db->prepare($query);
    $plant_name_like = "%" . $plant_name . "%";
    $stmt->bindParam(":plant_name", $plant_name_like);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($resultados) > 0) {
        $respuesta_formateada = [];
        $respuesta_texto = "🌿 ¡Excelente! Encontramos la planta '{$plant_name}' disponible en:\n\n";

        foreach ($resultados as $row) {
            $stock = (int)$row['stock'];
            $direccion = $row['direccion'] ?? 'Dirección no especificada';
            $telefonos = $row['telefonos'] ?? 'No disponible';
            $nombre_empresa = $row['nombre_empresa'] ?? 'Empresa desconocida';

            $respuesta_formateada[] = [
                'nombre' => $row['nombre_plantas'],
                'stock' => $stock,
                'sede' => $row['nombre_sede'],
                'direccion' => $direccion,
                'empresa' => $nombre_empresa,
                'contacto' => $telefonos
            ];

            $respuesta_texto .= "🟢 Empresa: {$nombre_empresa}\n";
            $respuesta_texto .= "   - Sede: {$row['nombre_sede']}\n";
            $respuesta_texto .= "   - Stock: {$stock}\n";
            $respuesta_texto .= "   - Ubicación: {$direccion}\n";
            $respuesta_texto .= "   - Contacto: 📞 {$telefonos}\n\n";
        }

        // --- Generar un párrafo informativo sobre la planta usando IA ---
        $prompt_info = "Escribe un párrafo breve, claro y amigable sobre la planta llamada '{$plant_name}'. 
        Describe sus características principales y algunos consejos básicos de cuidado. No uses lenguaje técnico.";

        $ch_info = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt($ch_info, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_info, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $OPENAI_API_KEY
        ]);

        $data_info = [
            "model" => "gpt-3.5-turbo",
            "messages" => [
                ["role" => "system", "content" => "Eres un experto en botánica que escribe descripciones cortas y fáciles de entender."],
                ["role" => "user", "content" => $prompt_info]
            ],
            "max_tokens" => 200,
            "temperature" => 0.8
        ];

        curl_setopt($ch_info, CURLOPT_POST, true);
        curl_setopt($ch_info, CURLOPT_POSTFIELDS, json_encode($data_info));

        $response_info = curl_exec($ch_info);
        curl_close($ch_info);

        $ai_info = json_decode($response_info, true);
        $descripcion_planta = trim($ai_info['choices'][0]['message']['content'] ?? '');

        if ($descripcion_planta !== '') {
            $respuesta_texto .= "📘 Información adicional:\n{$descripcion_planta}";
        } else {
            $respuesta_texto .= "📘 No se pudo generar información adicional en este momento.";
        }

        echo json_encode([
            "success" => true,
            "message" => $respuesta_texto,
            "results" => $respuesta_formateada
        ]);

    } else {
        // --- Si no se encuentra, usar la IA para generar información ---
        $prompt = "Dame información general, descripción y cuidados de la planta llamada '{$plant_name}'.";

        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $OPENAI_API_KEY
        ]);

        $data = [
            "model" => "gpt-3.5-turbo",
            "messages" => [
                ["role" => "system", "content" => "Eres un experto en botánica que explica de manera breve y clara."],
                ["role" => "user", "content" => $prompt]
            ],
            "max_tokens" => 300,
            "temperature" => 0.7
        ];

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $ai_result = json_decode($response, true);
        curl_close($ch);

        $ai_message = $ai_result['choices'][0]['message']['content'] ?? "No se pudo obtener respuesta de la IA.";

        echo json_encode([
            "success" => true,
            "message" => "No encontramos la planta '{$plant_name}' en los viveros registrados. Sin embargo, te dejo informacion adicional sobre la planta en cuestion:\n\n" . $ai_message,
            "results" => []
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
}

?>