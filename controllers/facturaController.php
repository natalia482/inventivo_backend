<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Factura.php';

class FacturaController {
    private $db;
    private $factura;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->factura = new Factura($this->db);
    }
    //obtener siguiente número    
    public function obtenerSiguienteNumeroFactura($id_empresa) {
        if (empty($id_empresa)) {
            return [
                "success" => false,
                "message" => "id_empresa es requerido"
            ];
        }
        $siguienteNumero = $this->factura->obtenerSiguienteNumeroFactura($id_empresa);
        return [
            "success" => true,
            "siguiente_numero" => (int)$siguienteNumero // Se devuelve como entero
        ];
    }

    // Crear factura
    public function agregarFactura($data) {
        // ... (lógica de debug y validación existente)

        // 1. Se llama a crearFactura sin el parámetro numero_factura (ya fue eliminado del modelo)
        $resultado = $this->factura->crearFactura(
            $data['id_empresa'],
            $data['id_vendedor'],
            $data['total'],
            $data['detalles']
        );

        echo json_encode($resultado);
    }

    // Listar facturas por empresa
    public function listarFacturas($id_empresa) {
        if (empty($id_empresa)) {
            echo json_encode([
                "success" => false,
                "message" => "id_empresa es requerido"
            ]);
            return;
        }

        $result = $this->factura->listar($id_empresa);
        echo json_encode([
            "success" => true,
            "data" => $result
        ]);
    }

    // Obtener detalle de una factura
    public function obtenerDetalleFactura($id_factura) {
        if (empty($id_factura)) {
            return [
                "success" => false,
                "message" => "id_factura es requerido"
            ];
        }

        $detalles = $this->factura->obtenerDetalle($id_factura);
        return [
            "success" => true,
            "data" => $detalles
        ];
    }

    // Eliminar factura
    public function eliminarFactura($id) {
        if (empty($id)) {
            echo json_encode([
                "success" => false,
                "message" => "Falta el ID de la factura"
            ]);
            return;
        }

        if ($this->factura->eliminar($id)) {
            echo json_encode([
                "success" => true,
                "message" => "Factura eliminada correctamente"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error al eliminar la factura"
            ]);
        }
    }

    // Obtener plantas disponibles para facturar
    public function obtenerProductosDisponibles($id_empresa) {
        if (empty($id_empresa)) {
            echo json_encode([
                "success" => false,
                "message" => "Falta el parámetro id_empresa"
            ]);
            return;
        }

        $productos = $this->factura->obtenerProductosDisponibles($id_empresa);
        
        echo json_encode([
            "success" => true,
            "data" => $productos,
            "message" => count($productos) > 0 
                ? "Productos disponibles encontrados" 
                : "No hay productos disponibles para facturar"
        ]);
    }
}
?>