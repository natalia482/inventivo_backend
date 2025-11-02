<?php
// Archivo: inventivo_backend/models/remision.php
class Remision {
    private $conn;
    private $table_name = "remisiones";

    public $id;
    public $numero_Remision; 
    public $id_sede; 
    public $id_vendedor;
    public $fecha_emision;
    public $total;
    public $nombre_cliente; 
    public $telefono_cliente; 

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerSiguienteNumeroRemision($id_sede) { 
        $query = "SELECT MAX(CAST(numero_remision AS UNSIGNED)) as ultimo_numero 
                  FROM remisiones 
                  WHERE id_sede = :id_sede";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_sede", $id_sede); 
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $ultimoNumero = $resultado['ultimo_numero'];
        
        return ($ultimoNumero === null || $ultimoNumero === 0) ? 1 : $ultimoNumero + 1;
    }

    // ✅ MODIFICADO: Añadido $id_usuario
    public function crearRemision($numero_Remision, $id_sede, $id_vendedor, $total, $detalles, $nombre_cliente, $telefono_cliente, $id_usuario) { 
        try {
            $this->conn->beginTransaction();

            $numero_final = $numero_Remision;
            if (empty($numero_Remision)) {
                $numero_final = $this->obtenerSiguienteNumeroRemision($id_sede); 
            }
            // --- Validación de Stock (Omitida para brevedad, asumiendo que funciona) ---
            foreach ($detalles as $detalle) {
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
            // --- Fin Validación de Stock ---

            // Insertar remisión
            $query = "INSERT INTO remisiones (numero_remision, id_sede, id_vendedor, total, nombre_cliente, telefono_cliente, fecha_emision) 
                      VALUES (:numero_remision, :id_sede, :id_vendedor, :total,:nombre_cliente, :telefono_cliente, NOW())"; 
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":numero_remision" => $numero_final,
                ":id_sede" => $id_sede, 
                ":id_vendedor" => $id_vendedor,
                ":total" => $total,
                ":nombre_cliente" => $nombre_cliente, 
                ":telefono_cliente" => $telefono_cliente,
            ]);
            $idRemision = $this->conn->lastInsertId();

            // Agregar detalles y descontar stock
            foreach ($detalles as $detalle) {
                $this->agregarDetalle($idRemision, $detalle["id_producto"], $detalle["cantidad"], $detalle["precio_unitario"]);
            }

            // ✅ AUDITORÍA: CREACIÓN
            $detalle_cambio = "Remisión N° {$numero_final} creada. Cliente: {$nombre_cliente}";
            $queryAuditoria = "INSERT INTO auditoria_movimientos 
                               (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                               VALUES (:id_usuario, :id_sede, 'remisiones', :id_registro, 'AGREGAR', :detalle)";
            
            $stmtAuditoria = $this->conn->prepare($queryAuditoria);
            $stmtAuditoria->execute([
                ':id_usuario' => $id_usuario, 
                ':id_sede' => $id_sede,
                ':id_registro' => $idRemision,
                ':detalle' => $detalle_cambio
            ]);

            $this->conn->commit();
            return ["success" => true, "message" => "Remisión creada exitosamente", "id_Remision" => $idRemision, "numero_factura" => strval($numero_final)];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Error en transacción: " . $e->getMessage()];
        }
    }
    
    // Agrega detalles a la tabla renombrada (se mantiene igual)
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

        $queryStock = "UPDATE plantas 
                       SET stock = stock - :cantidad
                       WHERE id = :idProducto";
        $stmt2 = $this->conn->prepare($queryStock);
        $stmt2->execute([
            ":cantidad" => intval($cantidad),
            ":idProducto" => intval($idProducto)
        ]);
    }

    // Listar remisiones por sede (se mantiene igual)
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

    // Obtener detalle de remisión (se mantiene igual)
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

    // ✅ MODIFICADO: Añadido $id_usuario y $id_sede para auditoría de eliminación
    public function eliminar($id, $id_usuario, $id_sede) {
        try {
            $this->conn->beginTransaction();

            // 1. Obtener los detalles
            $queryDetalles = "SELECT id_producto, cantidad FROM detalle_remision WHERE id_remision = :id";
            $stmtDetalles = $this->conn->prepare($queryDetalles);
            $stmtDetalles->execute([":id" => $id]);
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

            // 2. Devolver el stock (Omitido para brevedad)
            foreach ($detalles as $detalle) {
                $queryStock = "UPDATE plantas SET stock = stock + :cantidad WHERE id = :idProducto";
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

            // ✅ AUDITORÍA: ELIMINACIÓN
            $detalle_cambio = "Remisión ID: {$id} eliminada permanentemente. Stock devuelto.";
            $queryAuditoria = "INSERT INTO auditoria_movimientos 
                               (id_usuario, id_sede, tabla_afectada, id_registro_afectado, tipo_operacion, detalle_cambio)
                               VALUES (:id_usuario, :id_sede, 'remisiones', :id_registro, 'ELIMINAR', :detalle)";
            
            $stmtAuditoria = $this->conn->prepare($queryAuditoria);
            $stmtAuditoria->execute([
                ':id_usuario' => $id_usuario,
                ':id_sede' => $id_sede,
                ':id_registro' => $id,
                ':detalle' => $detalle_cambio
            ]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Fallo de Transacción (Rollback): " . $e->getMessage()];
        }
    }
}
?>