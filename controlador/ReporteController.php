<?php
class ReporteController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // --- AUTENTICACIÓN ADMINISTRADORES ---
    public function loginAdmin() {
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);
        $usuario = trim($input['usuario'] ?? $_POST['usuario'] ?? '');
        $contrasena = trim($input['contrasena'] ?? $_POST['contrasena'] ?? '');

        if (empty($usuario) || empty($contrasena)) {
            http_response_code(400);
            echo json_encode(["error" => "Por favor ingresa tu nombre o correo y tu contraseña."]);
            return;
        }

        try {
            // Consulta por nombre O por correo en la tabla usuarios_admin
            $sql = "SELECT * FROM usuarios_admin WHERE (nombre = :usuario OR correo = :usuario) LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();

            $admin = $stmt->fetch();

            // Verificación de contraseña (soporta texto plano y hash de password_hash)
            if ($admin && ($contrasena === $admin['contrasena'] || password_verify($contrasena, $admin['contrasena']))) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nombre'] = $admin['nombre'];
                $_SESSION['admin_correo'] = $admin['correo'];

                echo json_encode([
                    "mensaje" => "Inicio de sesión exitoso",
                    "admin" => [
                        "id" => $admin['id'],
                        "nombre" => $admin['nombre'],
                        "correo" => $admin['correo']
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["error" => "Credenciales incorrectas. Verifica tu usuario/correo y contraseña."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error al autenticar: " . $e->getMessage()]);
        }
    }

    public function logoutAdmin() {
        header('Content-Type: application/json; charset=utf-8');
        unset($_SESSION['admin_id'], $_SESSION['admin_nombre'], $_SESSION['admin_correo']);
        session_destroy();
        echo json_encode(["mensaje" => "Sesión cerrada correctamente"]);
    }

    public function verificarSesionAdmin() {
        header('Content-Type: application/json; charset=utf-8');
        if (isset($_SESSION['admin_id'])) {
            echo json_encode([
                "autenticado" => true,
                "nombre" => $_SESSION['admin_nombre'],
                "correo" => $_SESSION['admin_correo']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["autenticado" => false]);
        }
    }

    // --- GESTIÓN DE REPORTES ---
    public function listar() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $stmt = $this->db->prepare("SELECT * FROM reportes WHERE id = :id LIMIT 1");
                $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
                $stmt->execute();
                $reporte = $stmt->fetch();

                if ($reporte) {
                    echo json_encode($reporte);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Reporte no encontrado"]);
                }
                return;
            }

            $stmt = $this->db->prepare("SELECT * FROM reportes ORDER BY id DESC");
            $stmt->execute();
            $reportes = $stmt->fetchAll();
            echo json_encode($reportes);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error al consultar reportes: " . $e->getMessage()]);
        }
    }

    public function guardar() {
        header('Content-Type: application/json; charset=utf-8');

        $categoria = trim($_POST['tipo_incidencia'] ?? '');
        $ubicacionEscrita = trim($_POST['ubicacion'] ?? '');
        $descripcionUsuario = trim($_POST['descripcion'] ?? '');

        if (empty($categoria) || empty($ubicacionEscrita)) {
            http_response_code(400);
            echo json_encode(["error" => "El tipo de incidencia y la ubicación son obligatorios."]);
            return;
        }

        $latitud = null;
        $longitud = null;
        if (preg_match('/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/', $ubicacionEscrita, $coordenadas)) {
            $latitud = $coordenadas[1];
            $longitud = $coordenadas[2];
        }

        $descripcionFinal = "Ubicación: " . $ubicacionEscrita;
        if (!empty($descripcionUsuario)) {
            $descripcionFinal .= "\n\nDetalles: " . $descripcionUsuario;
        }

        $folio = "RC-" . date("dmy") . "-" . rand(1000, 9999);
        $titulo = ucfirst($categoria) . " en " . ($ubicacionEscrita ?: "Veracruz");

        $foto_url = null;
        if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
            $directorioSubidas = __DIR__ . '/../uploads/';
            if (!is_dir($directorioSubidas)) {
                mkdir($directorioSubidas, 0777, true);
            }

            $extension = pathinfo($_FILES['evidencia']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = uniqid('reporte_') . '.' . strtolower($extension);
            $rutaDestino = $directorioSubidas . $nombreArchivo;

            if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $rutaDestino)) {
                $foto_url = 'uploads/' . $nombreArchivo;
            } else {
                http_response_code(500);
                echo json_encode(["error" => "No se pudo guardar la imagen en el servidor."]);
                return;
            }
        }

        try {
            $sql = "INSERT INTO reportes 
                    (folio, titulo, descripcion, categoria, prioridad, latitud, longitud, foto_url, estatus, fecha_creacion) 
                    VALUES 
                    (:folio, :titulo, :descripcion, :categoria, 'media', :latitud, :longitud, :foto_url, 'reporte recibido', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':folio', $folio);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcionFinal);
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':latitud', $latitud);
            $stmt->bindParam(':longitud', $longitud);
            $stmt->bindParam(':foto_url', $foto_url);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    "mensaje" => "Reporte guardado correctamente",
                    "id" => $this->db->lastInsertId(),
                    "folio" => $folio
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "No se pudo insertar el registro."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error SQL: " . $e->getMessage()]);
        }
    }

    public function actualizarEstatus() {
        header('Content-Type: application/json; charset=utf-8');

        $inputJSON = json_decode(file_get_contents('php://input'), true);
        $data = is_array($inputJSON) ? $inputJSON : $_POST;

        $id = isset($data['id']) ? intval($data['id']) : 0;
        $estatus = trim(strtolower($data['estatus'] ?? ''));
        $prioridad = isset($data['prioridad']) ? trim(strtolower($data['prioridad'])) : null;

        $estatusPermitidos = ['reporte recibido', 'en revision', 'en proceso', 'resuelto', 'denegada'];

        if ($id <= 0 || !in_array($estatus, $estatusPermitidos, true)) {
            http_response_code(400);
            echo json_encode(["error" => "ID inválido o estatus no permitido."]);
            return;
        }

        try {
            if ($prioridad && in_array($prioridad, ['baja', 'media', 'alta', 'urgente'], true)) {
                $sql = "UPDATE reportes SET estatus = :estatus, prioridad = :prioridad WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':prioridad', $prioridad);
            } else {
                $sql = "UPDATE reportes SET estatus = :estatus WHERE id = :id";
                $stmt = $this->db->prepare($sql);
            }

            $stmt->bindParam(':estatus', $estatus);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode([
                    "mensaje" => "Reporte actualizado correctamente",
                    "id" => $id,
                    "estatus" => $estatus
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "No se pudo actualizar el reporte."]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error SQL al actualizar: " . $e->getMessage()]);
        }
    }
}