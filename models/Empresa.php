<?php
class Empresa {
    private $conn;
    private $table_name = "empresas";

    public $id;
    public $nombre_empresa;
    public $nit;
    public $direccion;
    public $id_usuario;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre_empresa, nit, direccion, id_usuario)
                  VALUES (:nombre_empresa, :nit, :direccion, :id_usuario)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre_empresa", $this->nombre_empresa);
        $stmt->bindParam(":nit", $this->nit);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>
