<?php
require_once __DIR__ . '/../modelo/config.php';

class ReporteController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    private function limpiarTexto(string $texto, int $maxLen, bool $permitirSaltos = false): string {
        $texto = mb_convert_encoding($texto, 'UTF-8', 'UTF-8');
        $patronControl = $permitirSaltos
            ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{202A}-\x{202E}\x{2066}-\x{2069}]/u'
            : '/[\x00-\x1F\x7F\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';
        $texto = preg_replace($patronControl, '', $texto) ?? '';
        $texto = preg_replace('/(\p{M}){3,}/u', '$1$1', $texto) ?? '';
        $texto = trim($texto);
        return mb_substr($texto, 0, $maxLen, 'UTF-8');
    }

    private function normalizarCategoria(string $cat): string {
        $cat = strtolower(trim($cat));
        $mapa = [
            'alumbrado' => 'luminaria',
            'fuga_agua' => 'fuga'
        ];
        $cat = $mapa[$cat] ?? $cat;
        return in_array($cat, CATEGORIAS_VALIDAS, true) ? $cat : 'otro';
    }

    private function normalizarEstatus(string $estatus): string {
        $st = strtolower(trim($estatus));
        $mapa = [
            'en analisis' => 'en revision',
            'en atencion' => 'en proceso',
            'cancelado'   => 'denegada'
        ];
        return $mapa[$st] ?? $st;
    }

    private function esAdminAutenticado(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['admin_id']) && intval($_SESSION['admin_id']) > 0;
    }

    private function estaEnElMar(float $lat, float $lng): bool {
        if ($lat < 19.0000 || $lat > 19.3000 || $lng < -96.3500 || $lng > -95.9500) {
            return true;
        }

        $costa = [
            [19.3000, -96.2100],
            [19.2400, -96.1820],
            [19.2250, -96.1620],
            [19.2120, -96.1480],
            [19.2080, -96.1255],
            [19.1980, -96.1285],
            [19.1860, -96.1185],
            [19.1700, -96.1115],
            [19.1450, -96.1005],
            [19.1250, -96.0955],
            [19.1020, -96.0975],
            [19.0920, -96.0840],
            [19.0750, -96.0390],
            [19.0550, -95.9790],
            [19.0000, -95.9500]
        ];

        for ($i = 0; $i < count($costa) - 1; $i++) {
            [$lat1, $lng1] = $costa[$i];
            [$lat2, $lng2] = $costa[$i + 1];
            if ($lat <= $lat1 && $lat >= $lat2) {
                $t = ($lat1 - $lat) / ($lat1 - $lat2);
                $lngCosta = $lng1 + $t * ($lng2 - $lng1);
                return $lng > $lngCosta;
            }
        }
        return false;
    }

    // --- AUTENTICACIÓN ADMINISTRADORES ---
    public function loginAdmin() {
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);
        $usuario = $this->limpiarTexto((string)($input['usuario'] ?? $_POST['usuario'] ?? ''), 120);
        $contrasena = trim((string)($input['contrasena'] ?? $_POST['contrasena'] ?? ''));

        if (empty($usuario) || empty($contrasena)) {
            http_response_code(400);
            echo json_encode(["error" => "Por favor ingresa tu nombre o correo y tu contraseña."]);
            return;
        }

        try {
            $sql = "SELECT * FROM usuarios_admin WHERE (nombre = :usuario OR correo = :usuario) LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();

            $admin = $stmt->fetch();

            if ($admin && password_verify($contrasena, $admin['contrasena'])) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);
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
            echo json_encode(["error" => "Error al autenticar."]);
        }
    }

    public function logoutAdmin() {
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
        echo json_encode(["mensaje" => "Sesión cerrada correctamente"]);
    }

    public function verificarSesionAdmin() {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->esAdminAutenticado()) {
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

    // --- LISTADO DE REPORTES ---
    public function listar() {
        header('Content-Type: application/json; charset=utf-8');
        $esAdmin = $this->esAdminAutenticado();

        try {
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $stmt = $this->db->prepare("SELECT * FROM reportes WHERE id = :id LIMIT 1");
                $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
                $stmt->execute();
                $reporte = $stmt->fetch();

                if (!$reporte) {
                    http_response_code(404);
                    echo json_encode(["error" => "Reporte no encontrado"]);
                    return;
                }

                $reporte['categoria'] = $this->normalizarCategoria($reporte['categoria'] ?? 'otro');
                $reporte['estatus']   = $this->normalizarEstatus($reporte['estatus'] ?? 'reporte recibido');

                if (!$esAdmin) {
                    unset($reporte['ciudadano_id'], $reporte['area_derivada']);
                }

                echo json_encode($reporte, JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($esAdmin && !isset($_GET['publico'])) {
                $stmt = $this->db->prepare("SELECT * FROM reportes ORDER BY id DESC");
            } else {
                $stmt = $this->db->prepare(
                    "SELECT id, folio, titulo, descripcion, categoria, prioridad, latitud, longitud, foto_url, estatus, fecha_creacion 
                     FROM reportes 
                     WHERE LOWER(estatus) NOT IN ('denegada', 'cancelado') 
                     ORDER BY id DESC"
                );
            }

            $stmt->execute();
            $reportes = $stmt->fetchAll();

            foreach ($reportes as &$rep) {
                $rep['categoria'] = $this->normalizarCategoria($rep['categoria'] ?? 'otro');
                $rep['estatus']   = $this->normalizarEstatus($rep['estatus'] ?? 'reporte recibido');
            }

            echo json_encode($reportes, JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error al consultar reportes."]);
        }
    }

    // --- GUARDAR REPORTE ---
    public function guardar() {
        header('Content-Type: application/json; charset=utf-8');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Control flexible por dispositivo/sesión (permite hasta MAX_REPORTES_POR_HORA_DISPOSITIVO)
        $ahora = time();
        $historialEnvios = $_SESSION['historial_reportes_ts'] ?? [];
        $historialEnvios = array_values(array_filter($historialEnvios, fn($ts) => ($ahora - $ts) < 3600));

        if (!empty($historialEnvios)) {
            $ultimoTs = end($historialEnvios);
            $transcurrido = $ahora - $ultimoTs;
            if ($transcurrido < COOLDOWN_SEGUNDOS_DISPOSITIVO) {
                $espera = COOLDOWN_SEGUNDOS_DISPOSITIVO - $transcurrido;
                http_response_code(429);
                echo json_encode(["error" => "Espera {$espera} segundo(s) antes de enviar otro reporte."]);
                return;
            }
        }

        if (count($historialEnvios) >= MAX_REPORTES_POR_HORA_DISPOSITIVO) {
            http_response_code(429);
            echo json_encode([
                "error" => "Has alcanzado el límite de " . MAX_REPORTES_POR_HORA_DISPOSITIVO . " reportes por hora desde este dispositivo."
            ]);
            return;
        }

        // 2. Categoría unificada y sanitización
        $categoria = $this->normalizarCategoria((string)($_POST['tipo_incidencia'] ?? 'otro'));
        $ubicacionEscrita = $this->limpiarTexto((string)($_POST['ubicacion'] ?? ''), 150, false);
        $descripcionUsuario = $this->limpiarTexto((string)($_POST['descripcion'] ?? ''), 1000, true);

        if (empty($ubicacionEscrita)) {
            http_response_code(400);
            echo json_encode(["error" => "La ubicación es obligatoria."]);
            return;
        }

        // 3. Validar coordenadas y bloquear mar
        $latitud = null;
        $longitud = null;
        if (preg_match('/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/', $ubicacionEscrita, $coordenadas)) {
            $latNum = (float)$coordenadas[1];
            $lngNum = (float)$coordenadas[2];

            if ($this->estaEnElMar($latNum, $lngNum)) {
                http_response_code(400);
                echo json_encode(["error" => "La ubicación seleccionada cae en el mar o fuera de Veracruz. Selecciona un punto en tierra firme."]);
                return;
            }
            $latitud = $coordenadas[1];
            $longitud = $coordenadas[2];
        }

        // 4. Verificación de duplicados por zona (desactivada por defecto para permitir acumulación en Mapa de Calor)
        if (BLOQUEAR_DUPLICADOS_ZONA) {
            try {
                if ($latitud !== null && $longitud !== null) {
                    $stmtDup = $this->db->prepare(
                        "SELECT folio FROM reportes 
                         WHERE categoria = :cat 
                           AND LOWER(estatus) NOT IN ('resuelto', 'denegada', 'cancelado')
                           AND latitud IS NOT NULL AND longitud IS NOT NULL
                           AND ABS(CAST(latitud AS DECIMAL(10,6)) - :lat) < 0.00045
                           AND ABS(CAST(longitud AS DECIMAL(10,6)) - :lng) < 0.00045
                           AND fecha_creacion >= (NOW() - INTERVAL 24 HOUR)
                         LIMIT 1"
                    );
                    $stmtDup->execute([
                        ':cat' => $categoria,
                        ':lat' => (float)$latitud,
                        ':lng' => (float)$longitud
                    ]);
                    $duplicado = $stmtDup->fetch();
                    if ($duplicado) {
                        http_response_code(409);
                        echo json_encode([
                            "error" => "Ya existe un reporte activo para esta incidencia en esa ubicación (Folio: {$duplicado['folio']})."
                        ]);
                        return;
                    }
                }
            } catch (PDOException $e) {}
        }

        // 5. Validar y guardar de 1 a 3 imágenes reales
        $archivos = [];
        if (isset($_FILES['evidencia'])) {
            if (is_array($_FILES['evidencia']['name'])) {
                $total = count($_FILES['evidencia']['name']);
                for ($i = 0; $i < $total; $i++) {
                    if ($_FILES['evidencia']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $archivos[] = [
                            'name'     => $_FILES['evidencia']['name'][$i],
                            'tmp_name' => $_FILES['evidencia']['tmp_name'][$i],
                            'error'    => $_FILES['evidencia']['error'][$i],
                            'size'     => $_FILES['evidencia']['size'][$i],
                        ];
                    }
                }
            } elseif ($_FILES['evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {
                $archivos[] = $_FILES['evidencia'];
            }
        }

        if (count($archivos) === 0 || count($archivos) > 3) {
            http_response_code(400);
            echo json_encode(["error" => "Debes adjuntar entre 1 y 3 fotografías de evidencia."]);
            return;
        }

        $directorioSubidas = __DIR__ . '/../uploads/';
        if (!is_dir($directorioSubidas)) {
            mkdir($directorioSubidas, 0777, true);
        }

        $mimesPermitidos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $rutasGuardadas = [];
        $archivosFisicos = [];

        foreach ($archivos as $idx => $archivo) {
            if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 8 * 1024 * 1024) {
                foreach ($archivosFisicos as $f) { @unlink($f); }
                http_response_code(400);
                echo json_encode(["error" => "La imagen #" . ($idx + 1) . " es inválida o supera los 8 MB."]);
                return;
            }

            $mimeReal = $finfo->file($archivo['tmp_name']);
            if (!isset($mimesPermitidos[$mimeReal]) || @getimagesize($archivo['tmp_name']) === false) {
                foreach ($archivosFisicos as $f) { @unlink($f); }
                http_response_code(400);
                echo json_encode(["error" => "El archivo #" . ($idx + 1) . " no es una imagen válida (solo JPG, PNG o WEBP)."]);
                return;
            }

            $nombreArchivo = uniqid('rep_') . '.' . $mimesPermitidos[$mimeReal];
            $rutaDestino = $directorioSubidas . $nombreArchivo;

            if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                $archivosFisicos[] = $rutaDestino;
                $rutasGuardadas[] = 'uploads/' . $nombreArchivo;
            } else {
                foreach ($archivosFisicos as $f) { @unlink($f); }
                http_response_code(500);
                echo json_encode(["error" => "No se pudo guardar una de las imágenes en el servidor."]);
                return;
            }
        }

        $foto_url = json_encode($rutasGuardadas, JSON_UNESCAPED_SLASHES);
        $descripcionFinal = "Ubicación: " . $ubicacionEscrita;
        if (!empty($descripcionUsuario)) {
            $descripcionFinal .= "\n\nDetalles: " . $descripcionUsuario;
        }
        $titulo = mb_substr(ucfirst($categoria) . " en " . $ubicacionEscrita, 0, 120, 'UTF-8');

        // 6. Generación de folio único con reintento ante colisión
        $folio = null;
        $reporteId = null;

        try {
            for ($intento = 0; $intento < 5; $intento++) {
                $candidato = "RC-" . date("dmy") . "-" . random_int(1000, 9999);

                $chk = $this->db->prepare("SELECT 1 FROM reportes WHERE folio = :folio LIMIT 1");
                $chk->execute([':folio' => $candidato]);
                if ($chk->fetch()) {
                    continue;
                }

                try {
                    $sql = "INSERT INTO reportes 
                            (folio, titulo, descripcion, categoria, prioridad, latitud, longitud, foto_url, estatus, fecha_creacion) 
                            VALUES 
                            (:folio, :titulo, :descripcion, :categoria, 'media', :latitud, :longitud, :foto_url, 'reporte recibido', NOW())";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        ':folio'       => $candidato,
                        ':titulo'      => $titulo,
                        ':descripcion' => $descripcionFinal,
                        ':categoria'   => $categoria,
                        ':latitud'     => $latitud,
                        ':longitud'    => $longitud,
                        ':foto_url'    => $foto_url
                    ]);
                    $folio = $candidato;
                    $reporteId = (int)$this->db->lastInsertId();
                    break;
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                }
            }

            if (!$folio || !$reporteId) {
                throw new RuntimeException("No se pudo generar un folio único tras varios intentos.");
            }

            try {
                $this->db->prepare("INSERT INTO historial_estatus (reporte_id, estatus) VALUES (?, 'reporte recibido')")
                         ->execute([$reporteId]);
            } catch (PDOException $e) {}

            $historialEnvios[] = time();
            $_SESSION['historial_reportes_ts'] = $historialEnvios;

            http_response_code(201);
            echo json_encode([
                "mensaje" => "Reporte guardado correctamente",
                "id" => $reporteId,
                "folio" => $folio,
                "total_fotos" => count($rutasGuardadas),
                "publicaciones_hora_dispositivo" => count($historialEnvios),
                "limite_hora_dispositivo" => MAX_REPORTES_POR_HORA_DISPOSITIVO
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            foreach ($archivosFisicos as $f) { @unlink($f); }
            http_response_code(500);
            echo json_encode(["error" => "Error al registrar el reporte: " . $e->getMessage()]);
        }
    }

    private function eliminarArchivosReporte(?string $fotoUrl): void {
        if (empty($fotoUrl)) return;

        $fotos = json_decode($fotoUrl, true);
        if (!is_array($fotos)) {
            $fotos = [$fotoUrl];
        }

        $uploadsDir = realpath(__DIR__ . '/../uploads');
        if (!$uploadsDir) return;

        foreach ($fotos as $ruta) {
            if (!is_string($ruta) || $ruta === '') continue;
            $archivoLocal = realpath(__DIR__ . '/../' . ltrim($ruta, '/'));
            if ($archivoLocal && strpos($archivoLocal, $uploadsDir) === 0 && is_file($archivoLocal)) {
                @unlink($archivoLocal);
            }
        }
    }

    // --- CAMBIO DE ESTATUS Y ELIMINACIÓN ---
    public function actualizarEstatus() {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->esAdminAutenticado()) {
            http_response_code(401);
            echo json_encode(["error" => "No autorizado. Inicia sesión como administrador."]);
            return;
        }

        $inputJSON = json_decode(file_get_contents('php://input'), true);
        $data = is_array($inputJSON) ? $inputJSON : $_POST;

        $ids = [];
        if (isset($data['ids']) && is_array($data['ids'])) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $data['ids']), fn($v) => $v > 0)));
        } elseif (isset($data['id'])) {
            $idUnico = intval($data['id']);
            if ($idUnico > 0) $ids[] = $idUnico;
        }

        $estatus = $this->normalizarEstatus((string)($data['estatus'] ?? ''));
        $prioridad = isset($data['prioridad']) ? trim(strtolower($data['prioridad'])) : null;

        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(["error" => "No se seleccionaron reportes válidos."]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($estatus === 'eliminar') {
            try {
                $stmtSel = $this->db->prepare("SELECT foto_url FROM reportes WHERE id IN ($placeholders)");
                $stmtSel->execute($ids);
                foreach ($stmtSel->fetchAll() as $rep) {
                    $this->eliminarArchivosReporte($rep['foto_url'] ?? null);
                }

                try {
                    $this->db->prepare("DELETE FROM notificaciones WHERE reporte_id IN ($placeholders)")->execute($ids);
                    $this->db->prepare("DELETE FROM historial_estatus WHERE reporte_id IN ($placeholders)")->execute($ids);
                } catch (PDOException $e) {}

                $stmtDel = $this->db->prepare("DELETE FROM reportes WHERE id IN ($placeholders)");
                $stmtDel->execute($ids);

                echo json_encode([
                    "mensaje" => "Reportes eliminados definitivamente",
                    "eliminados" => $stmtDel->rowCount()
                ], JSON_UNESCAPED_UNICODE);
                return;
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "Error SQL al eliminar."]);
                return;
            }
        }

        if (!in_array($estatus, ESTATUS_VALIDOS, true)) {
            http_response_code(400);
            echo json_encode(["error" => "Estatus no permitido."]);
            return;
        }

        try {
            if ($prioridad && in_array($prioridad, PRIORIDADES_VALIDAS, true)) {
                $sql = "UPDATE reportes SET estatus = ?, prioridad = ? WHERE id IN ($placeholders)";
                $params = array_merge([$estatus, $prioridad], $ids);
            } else {
                $sql = "UPDATE reportes SET estatus = ? WHERE id IN ($placeholders)";
                $params = array_merge([$estatus], $ids);
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $actualizados = $stmt->rowCount();

            $stmtInfo = $this->db->prepare(
                "SELECT r.id, r.folio, c.telefono 
                 FROM reportes r 
                 LEFT JOIN ciudadanos c ON c.id = r.ciudadano_id 
                 WHERE r.id IN ($placeholders)"
            );
            $stmtInfo->execute($ids);
            $reportesInfo = $stmtInfo->fetchAll();

            $stmtHist = $this->db->prepare("INSERT INTO historial_estatus (reporte_id, estatus) VALUES (?, ?)");
            $stmtNotif = $this->db->prepare("INSERT INTO notificaciones (reporte_id, telefono, estatus, mensaje) VALUES (?, ?, ?, ?)");

            foreach ($reportesInfo as $info) {
                try {
                    $stmtHist->execute([$info['id'], $estatus]);
                    if (!empty($info['telefono'])) {
                        $msgWa = "CiudadReporta: Tu reporte {$info['folio']} cambió de estatus a: {$estatus}.";
                        $stmtNotif->execute([$info['id'], $info['telefono'], $estatus, $msgWa]);
                    }
                } catch (PDOException $e) {}
            }

            echo json_encode([
                "mensaje" => "Reportes actualizados correctamente",
                "actualizados" => $actualizados,
                "estatus" => $estatus
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error SQL al actualizar."]);
        }
    }
}