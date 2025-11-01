<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/remision.php'; // Modificado

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
        
        if (empty($data['id_sede'])) { // Modificado
            $errores[] = "Falta id_sede";
        }
        if (empty($data['id_vendedor'])) {
            $errores[] = "Falta id_vendedor";
        }
        if (!isset($data['total']) || $data['total'] <= 0) {
            $errores[] = "Total inválido o falta";
        }
        if (empty($data['detalles']) || !is_array($data['detalles'])) {
            $errores[] = "Faltan detalles de productos";
        }
        
        if (!empty($errores)) {
            echo json_encode(["success" => false, "message" => "Faltan datos obligatorios: " . implode(", ", $errores)]);
            return;
        }

        $resultado = $this->remision->crearRemision(
            $data['numero_Remision'] ?? '', 
            $data['id_sede'], // Modificado
            $data['id_vendedor'],
            $data['total'],
            $data['detalles']
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
    public function eliminarRemision($id) {
        if (empty($id)) {
            echo json_encode(["success" => false, "message" => "Falta el ID de la remisión"]);
            return;
        }

        $resultado = $this->remision->eliminar($id);

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