<?php
require_once '../models/Insumo.php';

class InsumoController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar insumo
    public function registrar($data) {
        $insumo = new Insumo($this->conn);
        $insumo->nombre_insumo = $data["nombre_insumo"];
        $insumo->categoria = $data["categoria"];
        $insumo->precio = $data["precio"];
        $insumo->medida = $data["medida"];
        $insumo->cantidad = $data["cantidad"];
        $insumo->id_empresa = $data["id_empresa"];

        if ($insumo->registrar()) {
            echo json_encode(["success" => true, "message" => "Insumo registrado correctamente"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al registrar el insumo"]);
        }
    }

    // Listar insumos
    public function listar($id_empresa) {
        $insumo = new Insumo($this->conn);
        $insumo->id_empresa = $id_empresa;
        $stmt = $insumo->listar();
        $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $insumos]);
    }

    // Actualizar insumo
    public function actualizar($data) {
        $insumo = new Insumo($this->conn);
        $insumo->id = $data["id"];
        $insumo->nombre_insumo = $data["nombre_insumo"];
        $insumo->categoria = $data["categoria"];
        $insumo->precio = $data["precio"];
        $insumo->medida = $data["medida"];
        $insumo->cantidad = $data["cantidad"];

        if ($insumo->actualizar()) {
            echo json_encode(["success" => true, "message" => "Insumo actualizado correctamente"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al actualizar el insumo"]);
        }
    }

    // Buscar insumo
    public function buscar($keyword) {
        $insumo = new Insumo($this->conn);
        $stmt = $insumo->buscar($keyword);
        $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $insumos]);
    }
}
?>
