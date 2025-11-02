<?php
require_once '../config/conexion.php';

class Planta {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        // Aseguramos que la conexión lance excepciones
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    }

    // Agregar planta (ahora usa id_sede)
    public function agregar($nombre_plantas, $numero_bolsa, $precio, $categoria, $stock, $id_sede) {
        $query = "INSERT INTO plantas (nombre_plantas, numero_bolsa, precio, categoria, stock, id_sede, estado, fecha_creacion)
                  VALUES (?, ?, ?, ?, ?, ?, 'disponible', NOW())";
        $stmt = $this->conn->prepare($query);
        // El id_sede es el 6to parámetro
        return $stmt->execute([$nombre_plantas, $numero_bolsa, $precio, $categoria, $stock, $id_sede]);
    }

    // Actualizar plantas (ahora usa id_sede)
    public function actualizar($id, $nombre_plantas, $numero_bolsa, $precio, $categoria, $stock, $estado, $id_sede) {
        $query = "UPDATE plantas 
                SET nombre_plantas = :nombre_plantas,
                    numero_bolsa = :numero_bolsa,
                    precio = :precio,
                    categoria = :categoria,
                    stock = :stock,
                    estado = :estado
                WHERE id = :id AND id_sede = :id_sede"; // Aseguramos que solo edite en su sede

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":nombre_plantas", $nombre_plantas);
        $stmt->bindParam(":numero_bolsa", $numero_bolsa);
        $stmt->bindParam(":precio", $precio);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":stock", $stock);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id_sede", $id_sede); // Binding de id_sede

        return $stmt->execute();
    }

    // Listar plantas (ahora usa id_sede)
    public function listar($id_sede, $filtro = null) {
        if ($filtro) {
            $query = "SELECT id, nombre_plantas, numero_bolsa, precio, categoria, stock, estado, fecha_creacion 
                    FROM plantas 
                    WHERE id_sede = :id_sede 
                    AND (nombre_plantas LIKE :filtro OR categoria LIKE :filtro)
                    ORDER BY fecha_creacion DESC";
            $stmt = $this->conn->prepare($query);
            $likeFiltro = "%" . $filtro . "%";
            $stmt->bindParam(':id_sede', $id_sede);
            $stmt->bindParam(':filtro', $likeFiltro);
        } else {
            $query = "SELECT id, nombre_plantas, numero_bolsa, precio, categoria, stock, estado, fecha_creacion 
                    FROM plantas 
                    WHERE id_sede = :id_sede 
                    ORDER BY fecha_creacion DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_sede', $id_sede);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["success" => true, "data" => $data];
        } else {
            return ["success" => true, "data" => [], "message" => "No se encontraron resultados."];
        }
    }

    // Eliminación física (no requiere id_sede si el ID es único globalmente)
    public function eliminar($id) {
        $query = "DELETE FROM plantas WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Planta eliminada correctamente"];
        } else {
            return ["success" => false, "message" => "Error al eliminar la planta"];
        }
    }
}
?>