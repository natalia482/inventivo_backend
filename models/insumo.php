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
    public $id_empresa;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear insumo
    public function registrar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre_insumo, categoria, precio, medida, cantidad, id_empresa) 
                  VALUES (:nombre_insumo, :categoria, :precio, :medida, :cantidad, :id_empresa)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre_insumo", $this->nombre_insumo);
        $stmt->bindParam(":categoria", $this->categoria);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":medida", $this->medida);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":id_empresa", $this->id_empresa);

        return $stmt->execute();
    }

    // Listar insumos por empresa
    public function listar() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_empresa = :id_empresa ORDER BY fecha_registro DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_empresa", $this->id_empresa);
        $stmt->execute();
        return $stmt;
    }

    // Actualizar insumo
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre_insumo = :nombre_insumo, categoria = :categoria,
                      precio = :precio, medida = :medida, cantidad = :cantidad
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre_insumo", $this->nombre_insumo);
        $stmt->bindParam(":categoria", $this->categoria);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":medida", $this->medida);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Buscar insumo por nombre o categoría
    public function buscar($keyword) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE nombre_insumo LIKE :keyword OR categoria LIKE :keyword
                  ORDER BY fecha_registro DESC";
        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(":keyword", $keyword);
        $stmt->execute();
        return $stmt;
    }
}
?>
