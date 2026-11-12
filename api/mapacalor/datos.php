<?php
/**
 * ============================================================================
 * ARCHIVO: api/mapacalor/datos.php
 * PROPÓSITO: Endpoint JSON protegido que entrega las coordenadas y metadatos
 *            básicos de los reportes al mapa de calor administrativo.
 * ============================================================================
 */

// Activa el tipado estricto en PHP para evitar conversiones implícitas inseguras
declare(strict_types=1);

// ============================================================================
// SECCIÓN 1: CONTROL DE ACCESO Y VALIDACIÓN DE SESIÓN
// ============================================================================
// Inicia la sesión PHP si aún no existe una activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica si existe alguna variable de sesión válida asignada al iniciar sesión como dependencia
$sesionValida = !empty($_SESSION['usuario'])
    || !empty($_SESSION['usuario_id'])
    || !empty($_SESSION['user_id'])
    || !empty($_SESSION['id_usuario'])
    || !empty($_SESSION['admin'])
    || !empty($_SESSION['rol'])
    || !empty($_SESSION['user'])
    || !empty($_SESSION['logueado']);

// Si no hay sesión activa, bloquea la entrega de datos con código HTTP 401 (No autorizado)
if (!$sesionValida) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'No autorizado. Inicia sesión como dependencia.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================================
// SECCIÓN 2: CABECERAS HTTP Y CONFIGURACIÓN DE BASE DE DATOS
// ============================================================================
// Indica al navegador que la respuesta es JSON en UTF-8
header('Content-Type: application/json; charset=utf-8');
// Evita que el navegador guarde en caché los reportes del mapa de calor
header('Cache-Control: no-store');

// Parámetros de conexión a MySQL local (XAMPP)
const DB_HOST = '127.0.0.1';
const DB_NAME = 'reporte_ciudadano';
const DB_USER = 'root';
const DB_PASS = '';

// ============================================================================
// SECCIÓN 3: CONSULTA Y SERIALIZACIÓN DE REPORTES GEORREFERENCIADOS
// ============================================================================
try {
    // Establece la conexión PDO con manejo de errores por excepciones
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Obtiene el conteo total de reportes registrados en la tabla
    $total = (int)$pdo->query('SELECT COUNT(*) FROM reportes')->fetchColumn();

    // Consulta únicamente reportes con latitud y longitud válidas (sin datos personales)
    $filas = $pdo->query(
        'SELECT id, folio, titulo, descripcion, categoria, prioridad, estatus, latitud, longitud, fecha_creacion
         FROM reportes
         WHERE latitud IS NOT NULL AND longitud IS NOT NULL
           AND latitud BETWEEN -90 AND 90 AND longitud BETWEEN -180 AND 180
           AND NOT (latitud = 0 AND longitud = 0)
         ORDER BY id DESC
         LIMIT 20000'
    )->fetchAll();

    // Formatea cada fila a los tipos de datos esperados por el frontend (float para coordenadas, int para ID)
    $reportes = array_map(static fn(array $r): array => [
        'id'          => (int)$r['id'],
        'folio'       => $r['folio'],
        'titulo'      => $r['titulo'],
        'descripcion' => mb_substr((string)$r['descripcion'], 0, 300),
        'categoria'   => $r['categoria'],
        'prioridad'   => $r['prioridad'],
        'estatus'     => $r['estatus'],
        'lat'         => (float)$r['latitud'],
        'lng'         => (float)$r['longitud'],
        'fecha'       => $r['fecha_creacion'],
    ], $filas);

    // Devuelve la estructura JSON con totales y el arreglo de puntos
    echo json_encode(
        ['total' => $total, 'con_ubicacion' => count($reportes), 'reportes' => $reportes],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $e) {
    // Registra el detalle técnico en el log del servidor y envía mensaje seguro al cliente
    error_log('mapacalor/datos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo leer la base de datos. Revisa MySQL y las credenciales en datos.php.']);
}