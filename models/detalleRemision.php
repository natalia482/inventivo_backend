<?php
class DetalleRemision {
    private $conn;
    private $table_name = "detalle_Remision";

    public $id;
    public $id_Remision;
    public $id_producto;
    public $cantidad;
    public $precio_unitario;
    public $subtotal;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_Remision, id_producto, cantidad, precio_unitario, subtotal)
                  VALUES (:id_Remision, :id_producto, :cantidad, :precio_unitario, :subtotal)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_Remision', $this->id_Remision);
        $stmt->bindParam(':id_producto', $this->id_producto);
        $stmt->bindParam(':cantidad', $this->cantidad);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);
        $stmt->bindParam(':subtotal', $this->subtotal);
        return $stmt->execute();
    }

    public function listarPorRemision($id_Remision) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_Remision = :id_Remision";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_Remision', $id_Remision);
        $stmt->execute();
        return $stmt;
    }
}
?>
