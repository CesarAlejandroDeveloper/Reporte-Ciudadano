<?php
/**
 * datos.php — Entrega a mapacalor/index.html los reportes que tienen ubicación.
 * Solo lectura. No expone teléfonos ni nombres de ciudadanos.
 * Ruta: C:\xampp\htdocs\api\mapacalor\datos.php
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const DB_HOST = '127.0.0.1';
const DB_NAME = 'reporte_ciudadano';
const DB_USER = 'root';
const DB_PASS = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $total = (int)$pdo->query('SELECT COUNT(*) FROM reportes')->fetchColumn();

    $filas = $pdo->query(
        'SELECT id, folio, titulo, descripcion, categoria, prioridad, estatus, latitud, longitud, fecha_creacion
         FROM reportes
         WHERE latitud IS NOT NULL AND longitud IS NOT NULL
           AND latitud BETWEEN -90 AND 90 AND longitud BETWEEN -180 AND 180
           AND NOT (latitud = 0 AND longitud = 0)
         ORDER BY id DESC
         LIMIT 20000'
    )->fetchAll();

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

    echo json_encode(['total' => $total, 'con_ubicacion' => count($reportes), 'reportes' => $reportes],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('mapacalor/datos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo leer la base de datos. Revisa MySQL y las credenciales en datos.php.']);
}
