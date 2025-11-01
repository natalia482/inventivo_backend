<?php
require_once __DIR__ . '/../models/Plantas.php';

class PlantaController {
    private $model;

    public function __construct($db) {
        $this->model = new Planta($db);
    }

    public function agregar() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) { /* ... manejo de error ... */ }

        // Campos requeridos (ahora incluye id_sede)
        $nombre_plantas = $data["nombre_plantas"] ?? null;
        $numero_bolsa = $data["numero_bolsa"] ?? null;
        $precio = $data["precio"] ?? null;
        $categoria = $data["categoria"] ?? null;
        $stock = $data["stock"] ?? null;
        $id_sede = $data["id_sede"] ?? null; // Modificado

        if (!$nombre_plantas || !$precio || !$stock || !$id_sede) { // Modificado
            echo json_encode([
                "success" => false,
                "message" => "Faltan datos obligatorios (incluyendo id_sede)"
            ]);
            return;
        }

        $resultado = $this->model->agregar(
            $nombre_plantas,
            $numero_bolsa,
            $precio,
            $categoria,
            $stock,
            $id_sede // Modificado
        );

        echo json_encode([
            "success" => $resultado,
            "message" => $resultado ? "Planta registrada correctamente" : "Error al registrar planta"
        ]);
    }


    public function actualizar() {
        $data = json_decode(file_get_contents("php://input"), true);

        $id = $data["id"] ?? null;
        $id_sede = $data["id_sede"] ?? null; // Requerido para el WHERE
        // ... (otros campos)
        $estado = $data["estado"] ?? "disponible";

        if (!$id || !$id_sede) { // Modificado
            echo json_encode(["success" => false, "message" => "Falta el ID de la planta o ID de la Sede"]);
            return;
        }

        $resultado = $this->model->actualizar(
            $id,
            $data["nombre_plantas"] ?? '',
            $data["numero_bolsa"] ?? '',
            $data["precio"] ?? 0,
            $data["categoria"] ?? '',
            $data["stock"] ?? 0,
            $estado,
            $id_sede // Modificado
        );
        // ... (respuesta json)
    }

    public function listar($id_sede, $filtro = null) { // Modificado
      return $this->model->listar($id_sede, $filtro); // Modificado
    }

    public function eliminar($id) {
        return $this->model->eliminar($id);
    }
}
?>