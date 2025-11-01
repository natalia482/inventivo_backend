<?php
require_once '../../config/cors.php';
include_once '../../config/conexion.php';

$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// El Propietario envía el ID de la Empresa (no de la sede)
$id_empresa = isset($_GET['id_empresa']) ? $_GET['id_empresa'] : null;

if (empty($id_empresa)) {
    echo json_encode(["success" => false, "message" => "Falta el ID de la empresa."]);
    exit;
}

try {
    // Unimos auditoria con usuarios y sedes
    // Filtramos por id_empresa (ya que el Propietario ve todo)
    $query = "SELECT 
                a.id,
                a.fecha_cambio,
                a.tabla_afectada,
                a.tipo_operacion,
                a.detalle_cambio,
                CONCAT(u.nombre, ' ', u.apellido) as usuario_nombre,
                u.rol,
                s.nombre_sede
              FROM auditoria_movimientos a
              JOIN usuarios u ON a.id_usuario = u.id
              JOIN sedes s ON a.id_sede = s.id
              WHERE s.id_empresa = :id_empresa
              ORDER BY a.fecha_cambio DESC
              LIMIT 200"; // Limitar a los 200 cambios más recientes

    $stmt = $db->prepare($query);
    $stmt->bindParam(":id_empresa", $id_empresa, PDO::PARAM_INT);
    $stmt->execute();

    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $registros]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de BD: " . $e->getMessage()]);
}
?>