<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

include_once '../../../config/conexion.php';
$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtener datos del cuerpo JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validar datos recibidos
$id_insumo = $data['id_insumo'] ?? null;
$cantidad_utilizada = $data['cantidad_utilizada'] ?? null;
$dosificacion = $data['dosificacion'] ?? null; // Lo conservas por si lo usas más adelante
$objetivo = $data['objetivo'] ?? null;
$responsable = $data['responsable'] ?? null;
$id_sede = $data['id_sede'] ?? null;

// Validar campos obligatorios
if (!$id_insumo || !$cantidad_utilizada || !$id_sede || !$responsable) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan datos obligatorios (insumo, cantidad, sede o responsable)."
    ]);
    exit;
}

try {
    $db->beginTransaction();

    $cantidad_a_usar = floatval($cantidad_utilizada);

    // 1️⃣ Verificar stock actual (filtrando por sede)
    $checkStock = $db->prepare("
        SELECT cantidad 
        FROM insumos 
        WHERE id = :id_insumo AND id_sede = :id_sede
    ");
    $checkStock->execute([
        ':id_insumo' => $id_insumo,
        ':id_sede' => $id_sede
    ]);

    if ($checkStock->rowCount() == 0) {
        throw new Exception("Insumo no encontrado en esta sede.");
    }

    $stockActual = floatval($checkStock->fetchColumn());

    if ($stockActual < $cantidad_a_usar) {
        echo json_encode([
            "success" => false,
            "message" => "Stock insuficiente. Disponible: {$stockActual}, solicitado: {$cantidad_a_usar}."
        ]);
        $db->rollBack();
        exit;
    }

    // 2️⃣ Registrar la actividad agrícola
    $responsable_id = $data['responsable']; // el id del usuario
    $queryActividad = "
        INSERT INTO actividades_agricolas 
        (id_insumo, cantidad_utilizada, objetivo, responsable, id_sede, fecha)
        VALUES 
        (:id_insumo, :cantidad_utilizada, :objetivo, :responsable_id, :id_sede, NOW())
    ";
    $stmt = $db->prepare($queryActividad);
    $stmt->execute([
        ':id_insumo' => $id_insumo,
        ':cantidad_utilizada' => $cantidad_a_usar,
        ':objetivo' => $objetivo,
        ':responsable_id' => $responsable_id,
        ':id_sede' => $id_sede
    ]);

    // 3️⃣ Actualizar el stock del insumo (filtrando por sede)
    $queryStock = "
        UPDATE insumos
        SET cantidad = cantidad - :cantidad_utilizada,
            estado = CASE
                WHEN (cantidad - :cantidad_utilizada) <= 0 THEN 'NO DISPONIBLE'
                ELSE 'DISPONIBLE'
            END
        WHERE id = :id_insumo AND id_sede = :id_sede
    ";
    $stmtStock = $db->prepare($queryStock);
    $stmtStock->execute([
        ':cantidad_utilizada' => $cantidad_a_usar,
        ':id_insumo' => $id_insumo,
        ':id_sede' => $id_sede
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "✅ Actividad registrada y stock actualizado correctamente."
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        "success" => false,
        "message" => "❌ Error al registrar actividad: " . $e->getMessage()
    ]);
}
?>