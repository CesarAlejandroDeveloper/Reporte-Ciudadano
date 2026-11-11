<?php
class Reporte {
    private $conn;
    private $tabla = "reportes";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM " . $this->tabla . " ORDER BY fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function crear($datos) {
        $query = "INSERT INTO " . $this->tabla . " 
                  (titulo, descripcion, categoria, prioridad, latitud, longitud, foto_url, estatus) 
                  VALUES (:titulo, :descripcion, :categoria, :prioridad, :latitud, :longitud, :foto_url, 'pendiente')";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':titulo'      => $datos['titulo'] ?? '',
            ':descripcion' => $datos['descripcion'] ?? '',
            ':categoria'   => $datos['categoria'] ?? 'General',
            ':prioridad'   => $datos['prioridad'] ?? 'Media',
            ':latitud'     => $datos['latitud'] ?? null,
            ':longitud'    => $datos['longitud'] ?? null,
            ':foto_url'    => $datos['foto_url'] ?? null,
        ]);
    }

    public function actualizarEstatus($id, $estatus) {
    $query = "UPDATE " . $this->tabla . " SET estatus = :estatus WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    return $stmt->execute([
        ':estatus' => $estatus,
        ':id'      => $id
    ]);
}
}