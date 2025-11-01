<?php
class Sede {
    private $conn;
    private $table_name = "sedes";

    public $id;
    public $id_empresa;
    public $nombre_sede;
    public $direccion;
    public $latitud;
    public $longitud;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_empresa, nombre_sede, direccion, latitud, longitud)
                  VALUES (:id_empresa, :nombre_sede, :direccion, :latitud, :longitud)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_empresa", $this->id_empresa);
        $stmt->bindParam(":nombre_sede", $this->nombre_sede);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":latitud", $this->latitud);
        $stmt->bindParam(":longitud", $this->longitud);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>