<?php
class Factura {
    private $conn;
    private $table_name = "facturas"; // ✅ CORREGIDO: Usar 'facturas' consistentemente

    public $id;
    public $numero_factura;
    public $id_empresa;
    public $id_vendedor;
    public $fecha_emision;
    public $total;

    public function __construct($db) {
        $this->conn = $db;
    }
    public function crearFactura($numero_factura, $id_empresa, $id_vendedor, $total, $detalles) {
        try {
            $this->conn->beginTransaction();

            // Validar stock antes de procesar (Lógica omitida por brevedad, asumimos es correcta)
            // ...

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
        
        // ✅ CORRECCIÓN CLAVE: Se elimina 'subtotal' del INSERT INTO y VALUES.
        $query = "INSERT INTO detalle_factura (id_factura, id_producto, cantidad, precio_unitario)
                  VALUES (:id_factura, :id_producto, :cantidad, :precio_unitario)";
        $stmt = $this->conn->prepare($query);
        
        // Asegurar la tipificación
        $idFactura_int = intval($idFactura); 
        $idProducto_int = intval($idProducto);
        $cantidad_int = intval($cantidad);

        $stmt->execute([
            ":id_factura" => $idFactura_int,
            ":id_producto" => $idProducto_int,
            ":cantidad" => $cantidad_int,
            ":precio_unitario" => $precioUnitario,
        ]);

        // Descontar stock y actualizar estado si es necesario
        $queryStock = "UPDATE plantas 
                       SET stock = stock - :cantidad_resta,
                           estado = CASE 
                               WHEN (stock - :cantidad_condicional) <= 0 THEN 'no disponible'
                               ELSE estado
                           END
                       WHERE id = :idProducto";
        $stmt2 = $this->conn->prepare($queryStock);
        
        $stmt2->execute([
            ":cantidad_resta" => $cantidad_int,
            ":cantidad_condicional" => $cantidad_int,
            ":idProducto" => $idProducto_int
        ]);
    }

    // Listar facturas (Usa LEFT JOIN y 'facturas')
    public function listar($id_empresa) {
        $query = "SELECT f.*, 
                         CONCAT(u.nombre, ' ', u.apellido) AS vendedor
                  FROM facturas f 
                  LEFT JOIN usuarios u ON f.id_vendedor = u.id 
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

    // Eliminar factura (Usar 'facturas')
    public function eliminar($id) {
        try {
            $this->conn->beginTransaction();

            // 1. Obtener los detalles antes de eliminar
            $queryDetalles = "SELECT id_producto, cantidad FROM detalle_factura WHERE id_factura = :id";
            $stmtDetalles = $this->conn->prepare($queryDetalles);
            $stmtDetalles->execute([":id" => $id]);
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
            
            // Si no hay detalles, eliminamos solo la factura
            if (empty($detalles)) {
                $query = "DELETE FROM facturas WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([":id" => $id]);
                $this->conn->commit();
                return true;
            }

            // 2. Devolver el stock
            foreach ($detalles as $detalle) {
                $cantidad_devolver = intval($detalle['cantidad']);
                $idProducto_update = intval($detalle['id_producto']);
                
                // Usar nombres de parámetros únicos para el UPDATE
                $queryStock = "UPDATE plantas 
                               SET stock = stock + :cantidad_sumar,
                                   estado = CASE 
                                       WHEN (stock + :cantidad_condicional) > 0 THEN 'disponible'
                                       ELSE estado
                                   END
                               WHERE id = :idProducto";
                $stmtStock = $this->conn->prepare($queryStock);
                
                $stmtStock->execute([
                    ":cantidad_sumar"       => $cantidad_devolver,
                    ":cantidad_condicional" => $cantidad_devolver,
                    ":idProducto"           => $idProducto_update
                ]);
            }

            // 3. Eliminar detalles y factura
            $queryDeleteDetalles = "DELETE FROM detalle_factura WHERE id_factura = :id";
            $stmtDeleteDetalles = $this->conn->prepare($queryDeleteDetalles);
            $stmtDeleteDetalles->execute([":id" => $id]);

            $query = "DELETE FROM facturas WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([":id" => $id]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            // 🛑 DEVOLVEMOS EL MENSAJE DE ERROR REAL
            return ["success" => false, "message" => "Fallo de Transacción (Rollback): " . $e->getMessage()];
        }
    }
}
?>