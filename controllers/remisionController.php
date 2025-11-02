<?php
require_once  '../config/conexion.php';
require_once '../models/remision.php'; // Modificado

class RemisionController {
    private $db;
    private $remision; // Modificado

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->remision = new Remision($this->db); // Modificado
    }

    // Obtener siguiente número
    public function obtenerSiguienteNumeroRemision($id_sede) { // Modificado
        if (empty($id_sede)) {
            return ["success" => false, "message" => "id_sede es requerido"];
        }
        $siguienteNumero = $this->remision->obtenerSiguienteNumeroRemision($id_sede);
        return ["success" => true, "siguiente_numero" => (int)$siguienteNumero];
    }

    // Crear remisión
    public function agregarRemision($data) {
        $errores = [];
        
        if (empty($data['id_sede'])) { 
            $errores[] = "Falta id_sede";
        }
        if (empty($data['id_vendedor'])) {
            $errores[] = "Falta id_vendedor";
        }
        if (empty($data['id_usuario'])) { // Requerido para Auditoría
            $errores[] = "Falta id_usuario para auditoría";
        }
        if (!isset($data['total']) || $data['total'] <= 0) {
            $errores[] = "Total inválido o falta";
        }
        if (empty($data['detalles']) || !is_array($data['detalles'])) {
            $errores[] = "Faltan detalles de productos";
        }
        if (empty($data['nombre_cliente'])) {
            $errores[] = "Falta nombre_cliente";
        }
        if (empty($data['telefono_cliente'])) {
            $errores[] = "Falta telefono_cliente";
        }
        if (!empty($errores)) {
            echo json_encode(["success" => false, "message" => "Faltan datos obligatorios: " . implode(", ", $errores)]);
            return;
        }

        $resultado = $this->remision->crearRemision(
            $data['numero_Remision'] ?? '', 
            $data['id_sede'], 
            $data['id_vendedor'],
            $data['total'],
            $data['detalles'],
            $data['nombre_cliente'], 
            $data['telefono_cliente'],
            $data['id_usuario'],
            
        );

        echo json_encode($resultado);
    }

    // Listar remisiones
    public function listarRemisions($id_sede) { // Modificado
        if (empty($id_sede)) {
            echo json_encode(["success" => false, "message" => "id_sede es requerido"]);
            return;
        }

        $result = $this->remision->listar($id_sede);
        echo json_encode(["success" => true, "data" => $result]);
    }

    // Obtener detalle
    public function obtenerDetalleRemision($id_remision) { // Modificado
        if (empty($id_remision)) {
            return ["success" => false, "message" => "id_remision es requerido"];
        }

        $detalles = $this->remision->obtenerDetalle($id_remision);
        return ["success" => true, "data" => $detalles];
    }

    // Eliminar remisión
    public function eliminarRemision($id_remision_placeholder) {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? null;
        $id_usuario = $data['id_usuario'] ?? null;
        $id_sede = $data['id_sede'] ?? null;

        if (empty($id) || empty($id_usuario) || empty($id_sede)) {
            echo json_encode(["success" => false, "message" => "Faltan IDs de la remisión o IDs de auditoría."]);
            return;
        }

        $resultado = $this->remision->eliminar($id, $id_usuario, $id_sede); 

        if ($resultado === true) {
            echo json_encode(["success" => true, "message" => "Remisión eliminada correctamente"]);
        } else {
            $message = is_array($resultado) ? $resultado['message'] : "Error desconocido al eliminar.";
            echo json_encode(["success" => false, "message" => $message]);
        }
    }

    // Obtener plantas (ahora filtra por sede)
    public function obtenerProductosDisponibles($id_sede) {
        if (empty($id_sede)) {
            echo json_encode(["success" => false, "message" => "Falta el parámetro id_sede"]);
            return;
        }
        
        // (Asumiendo que esta lógica se moverá al modelo de Plantas)
        $query = "SELECT id, nombre_plantas, precio, stock 
                  FROM plantas 
                  WHERE id_sede = :id_sede AND estado = 'disponible' AND stock > 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_sede", $id_sede);
        $stmt->execute();
        
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "data" => $productos,
        ]);
    }
}
?>