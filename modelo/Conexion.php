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
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch(PDOException $e) {
            header("Content-Type: application/json");
            echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}