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
            "siguiente_numero" => (int)$siguienteNumero // Asegurar que sea entero para Flutter
        ];
    }
    // Crear factura
  public function agregarFactura($data) {
        // ... (validaciones de datos)
        
        // ✅ CORRECCIÓN CLAVE: Aseguramos los 5 argumentos, permitiendo que 'numero_factura' sea nulo.
        $resultado = $this->factura->crearFactura(
            $data['numero_factura'] ?? '', // 1. Argumento (Se envía null o '')
            $data['id_empresa'],          // 2. Argumento
            $data['id_vendedor'],         // 3. Argumento
            $data['total'],               // 4. Argumento
            $data['detalles']             // 5. Argumento
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
        $controller = new FacturaController();
        // Asumiendo que el modelo devuelve true O un array de error detallado
        $resultado = $controller->factura->eliminar($id); 
        if ($resultado === true) {
            echo json_encode([
                "success" => true,
                "message" => "Factura eliminada correctamente"
            ]);
        } else {
            // Si $resultado es el array de error del modelo, lo mostramos
            $message = is_array($resultado) && isset($resultado['message']) 
                       ? $resultado['message'] 
                       : "Error desconocido al eliminar la factura. Verifique logs.";
                       
            echo json_encode([
                "success" => false,
                "message" => $message
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