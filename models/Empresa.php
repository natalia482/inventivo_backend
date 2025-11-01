<?php
class Empresa {
    private $conn;
    private $table_name = "empresas";

    public $id;
    public $nombre_empresa;
    public $nit;
    public $direccion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        // Query actualizado sin id_usuario
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre_empresa, nit, direccion)
                  VALUES (:nombre_empresa, :nit, :direccion)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre_empresa", $this->nombre_empresa);
        $stmt->bindParam(":nit", $this->nit);
        $stmt->bindParam(":direccion", $this->direccion);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>