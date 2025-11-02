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
    public $telefonos; // ✅ CORRECCIÓN: Nombre de propiedad en plural

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        // ✅ CORRECCIÓN 1: La consulta SQL usa 'telefonos'
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_empresa, nombre_sede, direccion, latitud, longitud, telefonos)
                  VALUES (:id_empresa, :nombre_sede, :direccion, :latitud, :longitud, :telefonos)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_empresa", $this->id_empresa);
        $stmt->bindParam(":nombre_sede", $this->nombre_sede);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":latitud", $this->latitud);
        $stmt->bindParam(":longitud", $this->longitud);
        $stmt->bindParam(":telefonos", $this->telefonos); // ✅ CORRECCIÓN 2: El binding usa ':telefonos'

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>