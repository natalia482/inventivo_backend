<?php
require_once __DIR__ . '/../models/Plantas.php';

class PlantaController {
    private $model;

    public function __construct($db) {
        $this->model = new Planta($db);
    }

    public function agregar() {
        //  Lee el cuerpo JSON correctamente
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode([
                "success" => false,
                "message" => "No se recibieron datos válidos"
            ]);
            return;
        }

        //  Valida los campos requeridos
        $nombre_plantas = $data["nombre_plantas"] ?? null;
        $numero_bolsa = $data["numero_bolsa"] ?? null;
        $precio = $data["precio"] ?? null;
        $categoria = $data["categoria"] ?? null;
        $stock = $data["stock"] ?? null;
        $id_empresa = $data["id_empresa"] ?? null;

        if (!$nombre_plantas || !$numero_bolsa || !$precio || !$categoria || !$stock || !$id_empresa) {
            echo json_encode([
                "success" => false,
                "message" => "Faltan datos obligatorios"
            ]);
            return;
        }

        $resultado = $this->model->agregar(
            $nombre_plantas,
            $numero_bolsa,
            $precio,
            $categoria,
            $stock,
            $id_empresa
        );

        echo json_encode([
            "success" => $resultado,
            "message" => $resultado ? "Planta registrada correctamente" : "Error al registrar planta"
        ]);
    }


    public function actualizar() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode(["success" => false, "message" => "No se recibieron datos válidos"]);
            return;
        }

        $id = $data["id"] ?? null;
        $nombre_plantas = $data["nombre_plantas"] ?? null;
        $numero_bolsa = $data["numero_bolsa"] ?? null;
        $precio = $data["precio"] ?? null;
        $categoria = $data["categoria"] ?? null;
        $stock = $data["stock"] ?? null;
        $estado = $data["estado"] ?? "disponible";

        if (!$id) {
            echo json_encode(["success" => false, "message" => "Falta el ID de la planta"]);
            return;
        }

        $resultado = $this->model->actualizar(
            $id,
            $nombre_plantas,
            $numero_bolsa,
            $precio,
            $categoria,
            $stock,
            $estado
        );

        echo json_encode([
            "success" => $resultado,
            "message" => $resultado ? "Planta actualizada correctamente" : "Error al actualizar planta"
        ]);
    }

    public function listar($id_empresa, $filtro = null) {
      return $this->model->listar($id_empresa, $filtro);
    }

  // Aquí corregimos el método eliminar
    public function eliminar($id) {
        return $this->model->eliminar($id);
    }

}
