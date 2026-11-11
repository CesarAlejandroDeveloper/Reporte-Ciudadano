<?php
require_once __DIR__ . '/../modelo/Reporte.php';

class ReporteController {
    private $modelo;

    public function __construct($db) {
        $this->modelo = new Reporte($db);
    }

    public function listar() {
        header("Content-Type: application/json; charset=UTF-8");
        $reportes = $this->modelo->obtenerTodos();
        echo json_encode(["status" => "success", "data" => $reportes]);
    }

    public function guardar() {
        header("Content-Type: application/json; charset=UTF-8");
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if (empty($input['descripcion'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "La descripción es requerida"]);
            return;
        }

        if ($this->modelo->crear($input)) {
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Reporte creado exitosamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "No se pudo guardar el reporte"]);
        }
    }
}   