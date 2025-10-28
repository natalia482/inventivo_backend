<?php
require_once __DIR__ . '../../config/conexion.php';

class Planta {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    //  Agregar planta
    public function agregar($nombre_plantas, $numero_bolsa, $precio, $categoria, $stock, $id_empresa) {
        $query = "INSERT INTO plantas (nombre_plantas, numero_bolsa, precio, categoria, stock, id_empresa, estado, fecha_creacion)
                  VALUES (?, ?, ?, ?, ?, ?, 'disponible', NOW())";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$nombre_plantas, $numero_bolsa, $precio, $categoria, $stock, $id_empresa]);
    }

    //Actualizar plantas
    public function actualizar($id, $nombre_plantas, $numero_bolsa, $precio, $categoria, $stock, $estado) {
        $query = "UPDATE plantas 
                SET nombre_plantas = :nombre_plantas,
                    numero_bolsa = :numero_bolsa,
                    precio = :precio,
                    categoria = :categoria,
                    stock = :stock,
                    estado = :estado
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":nombre_plantas", $nombre_plantas);
        $stmt->bindParam(":numero_bolsa", $numero_bolsa);
        $stmt->bindParam(":precio", $precio);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":stock", $stock);
        $stmt->bindParam(":estado", $estado);

        return $stmt->execute();
    }

    public function listar($id_empresa, $filtro = null) {
        if ($filtro) {
            $query = "SELECT id, nombre_plantas, numero_bolsa, precio, categoria, stock, estado, fecha_creacion 
                    FROM plantas 
                    WHERE id_empresa = :id_empresa 
                    AND (nombre_plantas LIKE :filtro OR categoria LIKE :filtro)
                    ORDER BY fecha_creacion DESC";
            $stmt = $this->conn->prepare($query);
            $likeFiltro = "%" . $filtro . "%";
            $stmt->bindParam(':id_empresa', $id_empresa);
            $stmt->bindParam(':filtro', $likeFiltro);
        } else {
            $query = "SELECT id, nombre_plantas, numero_bolsa, precio, categoria, stock, estado, fecha_creacion 
                    FROM plantas 
                    WHERE id_empresa = :id_empresa 
                    ORDER BY fecha_creacion DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_empresa', $id_empresa);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ["success" => true, "data" => $data];
        } else {
            return ["success" => true, "data" => [], "message" => "No se encontraron resultados."];
        }
    }

    // Eliminación lógica (cambiar estado)
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
