<?php
class Remision {
    private $conn;
    private $table_name = "remisiones"; // Tabla renombrada

    public $id;
    public $numero_Remision; // Este campo en PHP sigue siendo un nombre interno
    public $id_sede; // Modificado
    public $id_vendedor;
    public $fecha_emision;
    public $total;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtiene el siguiente número basado en la sede
    public function obtenerSiguienteNumeroRemision($id_sede) { 
        // ✅ CORRECCIÓN: Se usa la columna exacta de la DB: 'numero_remision' 
        // y CAST(X AS UNSIGNED) para forzar la lectura secuencial de los números.
        $query = "SELECT MAX(CAST(numero_remision AS UNSIGNED)) as ultimo_numero 
                  FROM remisiones 
                  WHERE id_sede = :id_sede"; // Filtrado por sede
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_sede", $id_sede); 
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $ultimoNumero = $resultado['ultimo_numero'];
        
        // Si no hay remisiones, devuelve 1; de lo contrario, devuelve el siguiente.
        return ($ultimoNumero === null || $ultimoNumero === 0) ? 1 : $ultimoNumero + 1;
    }

    // Crea la remisión usando id_sede
    public function crearRemision($numero_Remision, $id_sede, $id_vendedor, $total, $detalles) { 
        try {
            $this->conn->beginTransaction();

            $numero_final = $numero_Remision;
            if (empty($numero_Remision)) {
                $numero_final = $this->obtenerSiguienteNumeroRemision($id_sede); 
            }

            // Validar stock (asumiendo que plantas usa id_sede)
            foreach ($detalles as $detalle) {
                // (La validación de stock debe asegurar que la planta pertenezca a la misma sede)
                $queryStock = "SELECT stock FROM plantas WHERE id = :id_producto AND id_sede = :id_sede";
                $stmtStock = $this->conn->prepare($queryStock);
                $stmtStock->execute([
                    ":id_producto" => $detalle["id_producto"],
                    ":id_sede" => $id_sede
                ]);
                $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);

                if (!$producto || $producto['stock'] < $detalle['cantidad']) {
                    throw new Exception("Stock insuficiente para el producto ID {$detalle['id_producto']} en esta sede.");
                }
            }

            // Insertar remisión
            // ✅ CORRECCIÓN: Usamos la columna exacta de la DB: 'numero_remision'
            $query = "INSERT INTO remisiones (numero_remision, id_sede, id_vendedor, total, fecha_emision) 
                      VALUES (:numero_remision, :id_sede, :id_vendedor, :total, NOW())"; 
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":numero_remision" => $numero_final,
                ":id_sede" => $id_sede, 
                ":id_vendedor" => $id_vendedor,
                ":total" => $total
            ]);
            $idRemision = $this->conn->lastInsertId();

            // Agregar detalles
            foreach ($detalles as $detalle) {
                $this->agregarDetalle($idRemision, $detalle["id_producto"], $detalle["cantidad"], $detalle["precio_unitario"]);
            }

            $this->conn->commit();
            // Aseguramos que el valor de retorno para Flutter use el nombre correcto
            return ["success" => true, "message" => "Remisión creada exitosamente", "id_Remision" => $idRemision, "numero_factura" => strval($numero_final)];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Error en transacción: " . $e->getMessage()];
        }
    }
    
    // Agrega detalles a la tabla renombrada
    private function agregarDetalle($idRemision, $idProducto, $cantidad, $precioUnitario) {
        $query = "INSERT INTO detalle_remision (id_remision, id_producto, cantidad, precio_unitario)
                  VALUES (:id_remision, :id_producto, :cantidad, :precio_unitario)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->execute([
            ":id_remision" => intval($idRemision),
            ":id_producto" => intval($idProducto),
            ":cantidad" => intval($cantidad),
            ":precio_unitario" => $precioUnitario,
        ]);

        // Descontar stock (la lógica de estado ya está en la tabla plantas)
        $queryStock = "UPDATE plantas 
                       SET stock = stock - :cantidad
                       WHERE id = :idProducto";
        $stmt2 = $this->conn->prepare($queryStock);
        $stmt2->execute([
            ":cantidad" => intval($cantidad),
            ":idProducto" => intval($idProducto)
        ]);
    }

    // Listar remisiones por sede
    public function listar($id_sede) { 
        $query = "SELECT f.*, 
                         CONCAT(u.nombre, ' ', u.apellido) AS vendedor
                  FROM remisiones f 
                  LEFT JOIN usuarios u ON f.id_vendedor = u.id 
                  WHERE f.id_sede = :id_sede
                  ORDER BY f.fecha_emision DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_sede", $id_sede); 
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener detalle de remisión
    public function obtenerDetalle($id_remision) { 
        $query = "SELECT df.*, p.nombre_plantas, p.categoria
                  FROM detalle_remision df
                  JOIN plantas p ON df.id_producto = p.id
                  WHERE df.id_remision = :id_remision";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_remision", $id_remision);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Eliminar remisión (Devolver stock)
    public function eliminar($id) {
        try {
            $this->conn->beginTransaction();

            // 1. Obtener los detalles
            $queryDetalles = "SELECT id_producto, cantidad FROM detalle_remision WHERE id_remision = :id";
            $stmtDetalles = $this->conn->prepare($queryDetalles);
            $stmtDetalles->execute([":id" => $id]);
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

            // 2. Devolver el stock
            foreach ($detalles as $detalle) {
                $queryStock = "UPDATE plantas 
                               SET stock = stock + :cantidad
                               WHERE id = :idProducto";
                $stmtStock = $this->conn->prepare($queryStock);
                $stmtStock->execute([
                    ":cantidad" => intval($detalle['cantidad']),
                    ":idProducto" => intval($detalle['id_producto'])
                ]);
            }

            // 3. Eliminar detalles
            $queryDeleteDetalles = "DELETE FROM detalle_remision WHERE id_remision = :id";
            $stmtDeleteDetalles = $this->conn->prepare($queryDeleteDetalles);
            $stmtDeleteDetalles->execute([":id" => $id]);

            // 4. Eliminar remisión
            $query = "DELETE FROM remisiones WHERE id = :id"; 
            $stmt = $this->conn->prepare($query);
            $stmt->execute([":id" => $id]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Fallo de Transacción (Rollback): " . $e->getMessage()];
        }
    }
}
?>