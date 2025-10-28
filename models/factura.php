<?php
class Factura {
    private $conn;
    private $table_name = "factura";

    public $id;
    public $numero_factura;
    public $id_empresa;
    public $id_vendedor;
    public $fecha_emision;
    public $total;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear factura
    public function crearFactura($numero_factura, $id_empresa, $id_vendedor, $total, $detalles) {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO factura (numero_factura, id_empresa, id_vendedor, total) 
                      VALUES (:numero_factura, :id_empresa, :id_vendedor, :total)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":numero_factura" => $numero_factura,
                ":id_empresa" => $id_empresa,
                ":id_vendedor" => $id_vendedor,
                ":total" => $total
            ]);
            $idFactura = $this->conn->lastInsertId();

            foreach ($detalles as $detalle) {
                $this->agregarDetalle($idFactura, $detalle["id_producto"], $detalle["cantidad"], $detalle["precio_unitario"]);
            }

            $this->conn->commit();
            return ["success" => true, "id_factura" => $idFactura];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    private function agregarDetalle($idFactura, $idProducto, $cantidad, $precioUnitario) {
        $subtotal = $cantidad * $precioUnitario;

        // Insertar detalle
        $query = "INSERT INTO detalle_factura (id_factura, id_producto, cantidad, precio_unitario, subtotal)
                  VALUES (:id_factura, :id_producto, :cantidad, :precio_unitario, :subtotal)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":id_factura" => $idFactura,
            ":id_producto" => $idProducto,
            ":cantidad" => $cantidad,
            ":precio_unitario" => $precioUnitario,
            ":subtotal" => $subtotal
        ]);

        // Restar stock del producto
        $queryStock = "UPDATE plantas SET stock = stock - :cantidad WHERE id = :idProducto";
        $stmt2 = $this->conn->prepare($queryStock);
        $stmt2->execute([
            ":cantidad" => $cantidad,
            ":idProducto" => $idProducto
        ]);
    }

    // Listar facturas
    public function listar($id_empresa) {
        $query = "SELECT f.*, u.nombre AS vendedor 
                  FROM factura f
                  JOIN usuarios u ON f.id_vendedor = u.id
                  WHERE f.id_empresa = :id_empresa
                  ORDER BY f.fecha_emision DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_empresa", $id_empresa);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Eliminar factura
    public function eliminar($id) {
        $query = "DELETE FROM factura WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
?>
