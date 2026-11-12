<?php
require_once __DIR__ . '/config.php';

class Conexion {
    public $conn;

    public function obtenerConexion() {
        $this->conn = null;
        try {
            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                $opciones
            );
        } catch (PDOException $exception) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["error" => "Error de conexión a BD: " . $exception->getMessage()]);
            exit();
        }
        return $this->conn;
    }
}