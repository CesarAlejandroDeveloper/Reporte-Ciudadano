<?php
/**
 * Controlador Principal de CiudadReporta
 *
 * Gestiona la autenticación de administradores, consulta pública y administrativa
 * de reportes (por ID o Folio), registro ciudadano con validación geográfica y
 * de archivos binarios, y actualización masiva de estatus.
 *
 * @package CiudadReporta\Controlador
 */

require_once __DIR__ . '/../modelo/config.php';

if (!defined('COOLDOWN_SEGUNDOS_DISPOSITIVO')) define('COOLDOWN_SEGUNDOS_DISPOSITIVO', 2);
if (!defined('MAX_REPORTES_POR_HORA_DISPOSITIVO')) define('MAX_REPORTES_POR_HORA_DISPOSITIVO', 150);
if (!defined('BLOQUEAR_DUPLICADOS_ZONA')) define('BLOQUEAR_DUPLICADOS_ZONA', false);

class ReporteController {
    /** @var PDO Conexión activa a la base de datos */
    private $db;

    /**
     * Constructor del controlador.
     *
     * @param PDO $db Instancia de conexión PDO.
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Sanitiza cadenas de entrada eliminando caracteres de control y marcas bidireccionales.
     *
     * @param string $texto          Cadena original.
     * @param int    $maxLen         Longitud máxima permitida.
     * @param bool   $permitirSaltos Permite saltos de línea si es true.
     * @return string Cadena limpia y recortada.
     */
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

    /**
     * Normaliza alias antiguos de categorías al vocabulario unificado.
     *
     * @param string $cat Categoría recibida.
     * @return string Categoría válida dentro de CATEGORIAS_VALIDAS.
     */
    private function normalizarCategoria(string $cat): string {
        $cat = strtolower(trim($cat));
        $mapa = [
            'alumbrado' => 'luminaria',
            'fuga_agua' => 'fuga'
        ];
        $cat = $mapa[$cat] ?? $cat;
        return in_array($cat, CATEGORIAS_VALIDAS, true) ? $cat : 'otro';
    }

    /**
     * Normaliza alias de estatus al vocabulario oficial del sistema.
     *
     * @param string $estatus Estatus recibido.
     * @return string Estatus normalizado.
     */
    private function normalizarEstatus(string $estatus): string {
        $st = strtolower(trim($estatus));
        $mapa = [
            'en analisis' => 'en revision',
            'en atencion' => 'en proceso',
            'cancelado'   => 'denegada'
        ];
        return $mapa[$st] ?? $st;
    }

    /**
     * Verifica si existe una sesión administrativa activa.
     *
     * @return bool True si el administrador está autenticado.
     */
    private function esAdminAutenticado(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['admin_id']) && intval($_SESSION['admin_id']) > 0;
    }

    /**
     * Comprueba si una coordenada cae en el Golfo de México o fuera del polígono de Veracruz.
     *
     * @param float $lat Latitud decimal.
     * @param float $lng Longitud decimal.
     * @return bool True si está en el mar o fuera de rango.
     */
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

    /**
     * Valida la contraseña de un administrador soportando bcrypt y formatos heredados
     * (texto plano, MD5 o SHA-256). Si coincide un formato heredado, lo migra
     * automáticamente a password_hash() en la base de datos.
     *
     * @param array  $admin      Registro obtenido de usuarios_admin.
     * @param string $contrasena Contraseña ingresada en el formulario.
     * @return bool True si la credencial es válida.
     */
    private function verificarYActualizarContrasena(array $admin, string $contrasena): bool {
        $guardada = (string)($admin['contrasena'] ?? '');
        if ($guardada === '') {
            return false;
        }

        // 1. Verificación estándar segura (bcrypt / argon2)
        if (password_verify($contrasena, $guardada)) {
            if (password_needs_rehash($guardada, PASSWORD_DEFAULT)) {
                $this->actualizarHashAdmin((int)$admin['id'], $contrasena);
            }
            return true;
        }

        // 2. Compatibilidad con registros heredados (texto plano, MD5 o SHA-256)
        $esTextoPlano = hash_equals($guardada, $contrasena);
        $esMd5        = (strlen($guardada) === 32) && hash_equals(strtolower($guardada), md5($contrasena));
        $esSha256     = (strlen($guardada) === 64) && hash_equals(strtolower($guardada), hash('sha256', $contrasena));

        if ($esTextoPlano || $esMd5 || $esSha256) {
            $this->actualizarHashAdmin((int)$admin['id'], $contrasena);
            return true;
        }

        return false;
    }

    /**
     * Asegura que la columna contrasena soporte 255 caracteres y guarda el nuevo hash seguro.
     *
     * @param int    $adminId    ID del administrador.
     * @param string $contrasena Contraseña en texto plano a cifrar.
     * @return void
     */
    private function actualizarHashAdmin(int $adminId, string $contrasena): void {
        try {
            $this->db->exec("ALTER TABLE usuarios_admin MODIFY contrasena VARCHAR(255) NOT NULL");
        } catch (PDOException $e) {
            // Ignorar si ya tiene longitud suficiente o sin permisos DDL
        }

        try {
            $nuevoHash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE usuarios_admin SET contrasena = :hash WHERE id = :id");
            $stmt->execute([
                ':hash' => $nuevoHash,
                ':id'   => $adminId
            ]);
        } catch (PDOException $e) {
            // No interrumpir el inicio de sesión si falla la actualización auxiliar
        }
    }

    // =========================================================================
    // 1. AUTENTICACIÓN DE ADMINISTRADORES
    // =========================================================================

    /**
     * Inicia sesión para un usuario administrador mediante nombre o correo electrónico.
     * Endpoint: POST /index.php/api/login
     *
     * @return void Emite respuesta JSON.
     */
    public function loginAdmin() {
        header('Content-Type: application/json; charset=utf-8');

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (!is_array($input)) {
            $input = [];
        }

        $usuarioRaw = $input['usuario'] ?? $_POST['usuario'] ?? '';
        $passRaw    = $input['contrasena'] ?? $_POST['contrasena'] ?? '';

        $usuario    = $this->limpiarTexto((string)$usuarioRaw, 120);
        $contrasena = trim((string)$passRaw);

        if ($usuario === '' || $contrasena === '') {
            http_response_code(400);
            echo json_encode(["error" => "Por favor ingresa tu nombre o correo y tu contraseña."], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $sql = "SELECT * FROM usuarios_admin 
                    WHERE LOWER(nombre) = LOWER(:usuario) OR LOWER(correo) = LOWER(:usuario) 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':usuario' => $usuario]);
            $admin = $stmt->fetch();

            if ($admin && $this->verificarYActualizarContrasena($admin, $contrasena)) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);
                $_SESSION['admin_id']     = (int)$admin['id'];
                $_SESSION['admin_nombre'] = $admin['nombre'];
                $_SESSION['admin_correo'] = $admin['correo'];

                echo json_encode([
                    "mensaje" => "Inicio de sesión exitoso",
                    "admin"   => [
                        "id"     => (int)$admin['id'],
                        "nombre" => $admin['nombre'],
                        "correo" => $admin['correo']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(401);
                echo json_encode(["error" => "Credenciales incorrectas. Verifica tu usuario/correo y contraseña."], JSON_UNESCAPED_UNICODE);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error interno al autenticar."], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Cierra la sesión administrativa actual y destruye la cookie de sesión.
     * Endpoint: GET/POST /index.php/api/logout
     *
     * @return void Emite respuesta JSON.
     */
    public function logoutAdmin() {
        header('Content-Type: application/json; charset=utf-8');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        echo json_encode(["mensaje" => "Sesión cerrada correctamente"], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Verifica si el navegador tiene una sesión de administrador activa.
     * Endpoint: GET /index.php/api/session
     *
     * @return void Emite respuesta JSON.
     */
    public function verificarSesionAdmin() {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->esAdminAutenticado()) {
            echo json_encode([
                "autenticado" => true,
                "id"          => $_SESSION['admin_id'],
                "nombre"      => $_SESSION['admin_nombre'],
                "correo"      => $_SESSION['admin_correo']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(401);
            echo json_encode(["autenticado" => false], JSON_UNESCAPED_UNICODE);
        }
    }

    // =========================================================================
    // 2. CONSULTA DE REPORTES (LISTADO Y DETALLE POR ID O FOLIO)
    // =========================================================================

    /**
     * Lista reportes o devuelve el detalle de un reporte específico por ?id= o ?folio=.
     * Endpoint: GET /index.php/api/reportes
     *
     * @return void Emite respuesta JSON.
     */
    public function listar() {
        header('Content-Type: application/json; charset=utf-8');
        $esAdmin = $this->esAdminAutenticado();

        try {
            $tieneId    = isset($_GET['id']) && is_numeric($_GET['id']);
            $tieneFolio = isset($_GET['folio']) && trim((string)$_GET['folio']) !== '';

            // Consulta individual para vista de detalle (Reporte.php)
            if ($tieneId || $tieneFolio) {
                if ($tieneId) {
                    $stmt = $this->db->prepare("SELECT * FROM reportes WHERE id = :id LIMIT 1");
                    $stmt->bindValue(':id', intval($_GET['id']), PDO::PARAM_INT);
                } else {
                    $folioBuscado = strtoupper(trim((string)$_GET['folio']));
                    $stmt = $this->db->prepare("SELECT * FROM reportes WHERE UPPER(folio) = :folio LIMIT 1");
                    $stmt->bindValue(':folio', $folioBuscado, PDO::PARAM_STR);
                }

                $stmt->execute();
                $reporte = $stmt->fetch();

                if (!$reporte) {
                    http_response_code(404);
                    echo json_encode(["error" => "Reporte no encontrado"], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $reporte['categoria'] = $this->normalizarCategoria($reporte['categoria'] ?? 'otro');
                $reporte['estatus']   = $this->normalizarEstatus($reporte['estatus'] ?? 'reporte recibido');

                // Adjuntar historial de estatus para la línea de tiempo
                $reporte['historial'] = [];
                try {
                    $stmtH = $this->db->prepare(
                        "SELECT estatus, creado FROM historial_estatus WHERE reporte_id = :rid ORDER BY id ASC"
                    );
                    $stmtH->execute([':rid' => intval($reporte['id'])]);
                    $reporte['historial'] = $stmtH->fetchAll();
                } catch (PDOException $e) {
                    // Ignorar si la tabla historial_estatus no está disponible
                }

                if (!$esAdmin) {
                    unset($reporte['ciudadano_id'], $reporte['area_derivada']);
                }

                echo json_encode($reporte, JSON_UNESCAPED_UNICODE);
                return;
            }

            // Listado general (administrativo vs público)
            if ($esAdmin && !isset($_GET['publico'])) {
                $stmt = $this->db->prepare("SELECT * FROM reportes ORDER BY id DESC");
                $stmt->execute();
            } else {
                $limite = (isset($_GET['limit']) && is_numeric($_GET['limit']))
                    ? max(1, min(150, intval($_GET['limit'])))
                    : null;

                $sql = "SELECT id, folio, titulo, descripcion, categoria, prioridad, latitud, longitud, foto_url, estatus, fecha_creacion 
                        FROM reportes 
                        WHERE LOWER(estatus) NOT IN ('denegada', 'cancelado') 
                        ORDER BY id DESC";

                if ($limite !== null) {
                    $sql .= " LIMIT :limite";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
                } else {
                    $stmt = $this->db->prepare($sql);
                }
                $stmt->execute();
            }

            $reportes = $stmt->fetchAll();

            foreach ($reportes as &$rep) {
                $rep['categoria'] = $this->normalizarCategoria($rep['categoria'] ?? 'otro');
                $rep['estatus']   = $this->normalizarEstatus($rep['estatus'] ?? 'reporte recibido');
            }

            echo json_encode($reportes, JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error al consultar reportes."], JSON_UNESCAPED_UNICODE);
        }
    }

    // =========================================================================
    // 3. CREACIÓN DE REPORTES CIUDADANOS
    // =========================================================================

    /**
     * Registra un nuevo reporte ciudadano validando tasa por dispositivo (hasta 150/h),
     * coordenadas en tierra firme y firmas binarias de 1 a 3 imágenes.
     * Endpoint: POST /index.php/api/reportes
     *
     * @return void Emite respuesta JSON.
     */
    public function guardar() {
        header('Content-Type: application/json; charset=utf-8');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Control flexible por dispositivo/sesión (hasta 150 reportes/hora)
        $ahora = time();
        $historialEnvios = $_SESSION['historial_reportes_ts'] ?? [];
        $historialEnvios = array_values(array_filter($historialEnvios, fn($ts) => ($ahora - $ts) < 3600));

        if (!empty($historialEnvios)) {
            $ultimoTs = end($historialEnvios);
            $transcurrido = $ahora - $ultimoTs;
            if ($transcurrido < COOLDOWN_SEGUNDOS_DISPOSITIVO) {
                $espera = COOLDOWN_SEGUNDOS_DISPOSITIVO - $transcurrido;
                http_response_code(429);
                echo json_encode(["error" => "Espera {$espera} segundo(s) antes de enviar otro reporte."], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        if (count($historialEnvios) >= MAX_REPORTES_POR_HORA_DISPOSITIVO) {
            http_response_code(429);
            echo json_encode([
                "error" => "Has alcanzado el límite de " . MAX_REPORTES_POR_HORA_DISPOSITIVO . " reportes por hora desde este dispositivo."
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $categoria          = $this->normalizarCategoria((string)($_POST['tipo_incidencia'] ?? 'otro'));
        $ubicacionEscrita   = $this->limpiarTexto((string)($_POST['ubicacion'] ?? ''), 150, false);
        $descripcionUsuario = $this->limpiarTexto((string)($_POST['descripcion'] ?? ''), 1000, true);

        if ($ubicacionEscrita === '') {
            http_response_code(400);
            echo json_encode(["error" => "La ubicación es obligatoria."], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validación de coordenadas y exclusión de mar
        $latitud = null;
        $longitud = null;
        if (preg_match('/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/', $ubicacionEscrita, $coordenadas)) {
            $latNum = (float)$coordenadas[1];
            $lngNum = (float)$coordenadas[2];

            if ($this->estaEnElMar($latNum, $lngNum)) {
                http_response_code(400);
                echo json_encode(["error" => "La ubicación seleccionada cae en el mar o fuera de Veracruz. Selecciona un punto en tierra firme."], JSON_UNESCAPED_UNICODE);
                return;
            }
            $latitud = $coordenadas[1];
            $longitud = $coordenadas[2];
        }

        // Validación opcional de duplicados en la misma zona
        if (BLOQUEAR_DUPLICADOS_ZONA && $latitud !== null && $longitud !== null) {
            try {
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
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
            } catch (PDOException $e) {
                // Ignorar error auxiliar
            }
        }

        // Procesamiento y validación binaria de 1 a 3 imágenes
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
            echo json_encode(["error" => "Debes adjuntar entre 1 y 3 fotografías de evidencia."], JSON_UNESCAPED_UNICODE);
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
                foreach ($archivosFisicos as $f) {
                    @unlink($f);
                }
                http_response_code(400);
                echo json_encode(["error" => "La imagen #" . ($idx + 1) . " es inválida o supera los 8 MB."], JSON_UNESCAPED_UNICODE);
                return;
            }

            $mimeReal = $finfo->file($archivo['tmp_name']);
            if (!isset($mimesPermitidos[$mimeReal]) || @getimagesize($archivo['tmp_name']) === false) {
                foreach ($archivosFisicos as $f) {
                    @unlink($f);
                }
                http_response_code(400);
                echo json_encode(["error" => "El archivo #" . ($idx + 1) . " no es una imagen válida (solo JPG, PNG o WEBP)."], JSON_UNESCAPED_UNICODE);
                return;
            }

            $nombreArchivo = uniqid('rep_') . '.' . $mimesPermitidos[$mimeReal];
            $rutaDestino = $directorioSubidas . $nombreArchivo;

            if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                $archivosFisicos[] = $rutaDestino;
                $rutasGuardadas[] = 'uploads/' . $nombreArchivo;
            } else {
                foreach ($archivosFisicos as $f) {
                    @unlink($f);
                }
                http_response_code(500);
                echo json_encode(["error" => "No se pudo guardar una de las imágenes en el servidor."], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $foto_url = json_encode($rutasGuardadas, JSON_UNESCAPED_SLASHES);
        $descripcionFinal = "Ubicación: " . $ubicacionEscrita;
        if ($descripcionUsuario !== '') {
            $descripcionFinal .= "\n\nDetalles: " . $descripcionUsuario;
        }
        $titulo = mb_substr(ucfirst($categoria) . " en " . $ubicacionEscrita, 0, 120, 'UTF-8');

        // Generación de folio único con reintento ante colisión
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
            } catch (PDOException $e) {
                // Ignorar si no existe historial_estatus
            }

            $historialEnvios[] = time();
            $_SESSION['historial_reportes_ts'] = $historialEnvios;

            http_response_code(201);
            echo json_encode([
                "mensaje"                        => "Reporte guardado correctamente",
                "id"                             => $reporteId,
                "folio"                          => $folio,
                "total_fotos"                    => count($rutasGuardadas),
                "publicaciones_hora_dispositivo" => count($historialEnvios),
                "limite_hora_dispositivo"        => MAX_REPORTES_POR_HORA_DISPOSITIVO
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            foreach ($archivosFisicos as $f) {
                @unlink($f);
            }
            http_response_code(500);
            echo json_encode(["error" => "Error al registrar el reporte: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // =========================================================================
    // 4. ACTUALIZACIÓN DE ESTATUS Y ELIMINACIÓN ADMINISTRATIVA
    // =========================================================================

    /**
     * Elimina los archivos físicos de evidencia asociados a un reporte.
     *
     * @param string|null $fotoUrl Cadena JSON o ruta individual de la foto.
     * @return void
     */
    private function eliminarArchivosReporte(?string $fotoUrl): void {
        if (empty($fotoUrl)) {
            return;
        }

        $fotos = json_decode($fotoUrl, true);
        if (!is_array($fotos)) {
            $fotos = [$fotoUrl];
        }

        $uploadsDir = realpath(__DIR__ . '/../uploads');
        if (!$uploadsDir) {
            return;
        }

        foreach ($fotos as $ruta) {
            if (!is_string($ruta) || $ruta === '') {
                continue;
            }
            $archivoLocal = realpath(__DIR__ . '/../' . ltrim($ruta, '/'));
            if ($archivoLocal && strpos($archivoLocal, $uploadsDir) === 0 && is_file($archivoLocal)) {
                @unlink($archivoLocal);
            }
        }
    }

    /**
     * Actualiza el estatus/prioridad o elimina definitivamente uno o varios reportes.
     * Endpoint: POST/PUT /index.php/api/reportes/estatus
     *
     * @return void Emite respuesta JSON.
     */
    public function actualizarEstatus() {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->esAdminAutenticado()) {
            http_response_code(401);
            echo json_encode(["error" => "No autorizado. Inicia sesión como administrador."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $inputJSON = json_decode(file_get_contents('php://input'), true);
        $data = is_array($inputJSON) ? $inputJSON : $_POST;

        $ids = [];
        if (isset($data['ids']) && is_array($data['ids'])) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $data['ids']), fn($v) => $v > 0)));
        } elseif (isset($data['id'])) {
            $idUnico = intval($data['id']);
            if ($idUnico > 0) {
                $ids[] = $idUnico;
            }
        }

        $estatus = $this->normalizarEstatus((string)($data['estatus'] ?? ''));
        $prioridad = isset($data['prioridad']) ? trim(strtolower($data['prioridad'])) : null;

        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(["error" => "No se seleccionaron reportes válidos."], JSON_UNESCAPED_UNICODE);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Eliminación definitiva (registro + historial + notificaciones + fotos en disco)
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
                } catch (PDOException $e) {
                    // Ignorar si no existen las tablas auxiliares
                }

                $stmtDel = $this->db->prepare("DELETE FROM reportes WHERE id IN ($placeholders)");
                $stmtDel->execute($ids);

                echo json_encode([
                    "mensaje"    => "Reportes eliminados definitivamente",
                    "eliminados" => $stmtDel->rowCount()
                ], JSON_UNESCAPED_UNICODE);
                return;
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "Error SQL al eliminar."], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        if (!in_array($estatus, ESTATUS_VALIDOS, true)) {
            http_response_code(400);
            echo json_encode(["error" => "Estatus no permitido."], JSON_UNESCAPED_UNICODE);
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
                } catch (PDOException $e) {
                    // Ignorar si no existen las tablas auxiliares
                }
            }

            echo json_encode([
                "mensaje"      => "Reportes actualizados correctamente",
                "actualizados" => $actualizados,
                "estatus"      => $estatus
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error SQL al actualizar."], JSON_UNESCAPED_UNICODE);
        }
    }
}