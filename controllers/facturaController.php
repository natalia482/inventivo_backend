<?php
require_once '../../config/conexion.php';
require_once '../../models/factura.php';

$database = new Database();
$db = $database->getConnection();
$factura = new Factura($db);

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $resultado = $factura->crearFactura(
            $data['numero_factura'],
            $data['id_empresa'],
            $data['id_vendedor'],
            $data['total'],
            $data['detalles']
        );
        echo json_encode($resultado);
        break;

    case 'GET':
        $id_empresa = $_GET['id_empresa'] ?? null;
        if ($id_empresa) {
            $result = $factura->listar($id_empresa);
            echo json_encode(["success" => true, "data" => $result]);
        } else {
            echo json_encode(["success" => false, "message" => "id_empresa requerido"]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if ($id && $factura->eliminar($id)) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>
