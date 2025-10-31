<?php
// Fichero: inventivo_backend/.../api/facturas/eliminar.php
require_once '../../controllers/facturaController.php';
require_once '../../config/cors.php';

// Aseguramos que la solicitud OPTIONS/CORS se maneje primero.

// ✅ Validamos que el ID venga del parámetro GET
$id = isset($_GET['id']) ? $_GET['id'] : null; 

if (empty($id)) {
    // Retornamos un error de FACTURA, no de PLANTA.
    echo json_encode(["success" => false, "message" => "Falta el ID de la factura a eliminar."]);
    exit;
}

$controller = new FacturaController();
$controller->eliminarFactura($id);
?>