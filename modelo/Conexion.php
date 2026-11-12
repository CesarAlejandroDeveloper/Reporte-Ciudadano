<?php
class Conexion {
    private $host = "localhost";
    private $db_name = "reporte_ciudadano";
    private $username = "root";
    private $password = "";
    public $conn;

    public function obtenerConexion() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error" => "Error de conexión a BD: " . $exception->getMessage()]);
            exit();
        }
        return $this->conn;
    }
}