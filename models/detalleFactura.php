<?php
class DetalleFactura {
    private $conn;
    private $table_name = "detalle_factura";

    public $id;
    public $id_factura;
    public $id_producto;
    public $cantidad;
    public $precio_unitario;
    public $subtotal;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_factura, id_producto, cantidad, precio_unitario, subtotal)
                  VALUES (:id_factura, :id_producto, :cantidad, :precio_unitario, :subtotal)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_factura', $this->id_factura);
        $stmt->bindParam(':id_producto', $this->id_producto);
        $stmt->bindParam(':cantidad', $this->cantidad);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);
        $stmt->bindParam(':subtotal', $this->subtotal);
        return $stmt->execute();
    }

    public function listarPorFactura($id_factura) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_factura = :id_factura";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_factura', $id_factura);
        $stmt->execute();
        return $stmt;
    }
}
?>
