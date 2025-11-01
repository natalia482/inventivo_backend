<?php
class Insumo {
    private $conn;
    private $table_name = "insumos";

    public $id;
    public $nombre_insumo;
    public $categoria;
    public $precio;
    public $medida;
    public $cantidad;
    public $id_sede; // Modificado
    public $estado;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Crear insumo (ahora usa id_sede y calcula estado)
    public function registrar() {
        $estado_calculado = (floatval($this->cantidad) > 0) ? 'DISPONIBLE' : 'NO DISPONIBLE';
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre_insumo, categoria, precio, medida, cantidad, id_sede, estado, fecha_registro) 
                  VALUES (:nombre_insumo, :categoria, :precio, :medida, :cantidad, :id_sede, :estado, NOW())";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre_insumo", $this->nombre_insumo);
        $stmt->bindParam(":categoria", $this->categoria);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":medida", $this->medida);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":id_sede", $this->id_sede); // Modificado
        $stmt->bindParam(":estado", $estado_calculado);

        return $stmt->execute();
    }

    // Listar insumos por sede
    public function listar($id_sede, $filtro = null) { // Modificado
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_sede = :id_sede"; // Modificado
        
        if ($filtro) {
            $query .= " AND (nombre_insumo LIKE :filtro OR categoria LIKE :filtro)";
        }
        $query .= " ORDER BY fecha_registro DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_sede", $id_sede); // Modificado
        
        if ($filtro) {
             $likeFiltro = "%" . $filtro . "%";
             $stmt->bindParam(':filtro', $likeFiltro);
        }
        
        $stmt->execute();
        return $stmt;
    }

    // Actualizar insumo (ahora usa id_sede)
    public function actualizar() {
        $estado_calculado = (floatval($this->cantidad) > 0) ? 'DISPONIBLE' : 'NO DISPONIBLE';

        $query = "UPDATE " . $this->table_name . "
                  SET nombre_insumo = :nombre_insumo, categoria = :categoria,
                      precio = :precio, medida = :medida, cantidad = :cantidad,
                      estado = :estado
                  WHERE id = :id AND id_sede = :id_sede"; // Modificado
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre_insumo", $this->nombre_insumo);
        $stmt->bindParam(":categoria", $this->categoria);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":medida", $this->medida);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":estado", $estado_calculado);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":id_sede", $this->id_sede); // Modificado

        return $stmt->execute();
    }
    
    // Eliminar insumo (eliminación física)
    public function eliminar($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
?>