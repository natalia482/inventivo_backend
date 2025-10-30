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

    // Crear factura con validación de stock
    public function crearFactura($numero_factura, $id_empresa, $id_vendedor, $total, $detalles) {
        try {
            $this->conn->beginTransaction();

            // Validar stock antes de procesar
            foreach ($detalles as $detalle) {
                $queryStock = "SELECT stock, nombre_plantas FROM plantas WHERE id = :id_producto";
                $stmtStock = $this->conn->prepare($queryStock);
                $stmtStock->execute([":id_producto" => $detalle["id_producto"]]);
                $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);

                if (!$producto) {
                    throw new Exception("Producto con ID {$detalle['id_producto']} no encontrado");
                }

                if ($producto['stock'] < $detalle['cantidad']) {
                    throw new Exception("Stock insuficiente para '{$producto['nombre_plantas']}'. Disponible: {$producto['stock']}, Solicitado: {$detalle['cantidad']}");
                }
            }

            // Insertar factura
            $query = "INSERT INTO facturas (numero_factura, id_empresa, id_vendedor, total, fecha_emision) 
                      VALUES (:numero_factura, :id_empresa, :id_vendedor, :total, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":numero_factura" => $numero_factura,
                ":id_empresa" => $id_empresa,
                ":id_vendedor" => $id_vendedor,
                ":total" => $total
            ]);
            $idFactura = $this->conn->lastInsertId();

            // Agregar detalles y descontar stock
            foreach ($detalles as $detalle) {
                $this->agregarDetalle($idFactura, $detalle["id_producto"], $detalle["cantidad"], $detalle["precio_unitario"]);
            }

            $this->conn->commit();
            return ["success" => true, "message" => "Factura creada exitosamente", "id_factura" => $idFactura];

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

        // Descontar stock y actualizar estado si es necesario
        $queryStock = "UPDATE plantas 
                       SET stock = stock - :cantidad,
                           estado = CASE 
                               WHEN (stock - :cantidad) <= 0 THEN 'no disponible'
                               ELSE estado
                           END
                       WHERE id = :idProducto";
        $stmt2 = $this->conn->prepare($queryStock);
        $stmt2->execute([
            ":cantidad" => $cantidad,
            ":idProducto" => $idProducto
        ]);
    }

    // Listar facturas con información del vendedor
    public function listar($id_empresa) {
        $query = "SELECT f.*, 
                         CONCAT(u.nombre, ' ', u.apellido) AS vendedor
                  FROM factura f
                  JOIN usuarios u ON f.id_vendedor = u.id
                  WHERE f.id_empresa = :id_empresa
                  ORDER BY f.fecha_emision DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_empresa", $id_empresa);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener detalle completo de una factura
    public function obtenerDetalle($id_factura) {
        $query = "SELECT df.*, p.nombre_plantas, p.categoria
                  FROM detalle_factura df
                  JOIN plantas p ON df.id_producto = p.id
                  WHERE df.id_factura = :id_factura";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_factura", $id_factura);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Eliminar factura (devolver stock)
    public function eliminar($id) {
        try {
            $this->conn->beginTransaction();

            // Obtener los detalles antes de eliminar
            $queryDetalles = "SELECT id_producto, cantidad FROM detalle_factura WHERE id_factura = :id";
            $stmtDetalles = $this->conn->prepare($queryDetalles);
            $stmtDetalles->execute([":id" => $id]);
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

            // Devolver el stock
            foreach ($detalles as $detalle) {
                $queryStock = "UPDATE plantas 
                               SET stock = stock + :cantidad,
                                   estado = CASE 
                                       WHEN (stock + :cantidad) > 0 THEN 'disponible'
                                       ELSE estado
                                   END
                               WHERE id = :idProducto";
                $stmtStock = $this->conn->prepare($queryStock);
                $stmtStock->execute([
                    ":cantidad" => $detalle['cantidad'],
                    ":idProducto" => $detalle['id_producto']
                ]);
            }

            // Eliminar detalles
            $queryDeleteDetalles = "DELETE FROM detalle_factura WHERE id_factura = :id";
            $stmtDeleteDetalles = $this->conn->prepare($queryDeleteDetalles);
            $stmtDeleteDetalles->execute([":id" => $id]);

            // Eliminar factura
            $query = "DELETE FROM factura WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([":id" => $id]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>