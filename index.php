<?php
// Configuración de cabeceras CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/modelo/Conexion.php';
require_once __DIR__ . '/controlador/ReporteController.php';

$database = new Conexion();
$db = $database->obtenerConexion();
$controller = new ReporteController($db);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// 1. RUTAS DE LA API (Para llamadas Fetch / AJAX)
if (strpos($uri, '/api/reportes') !== false) {
    if ($method === 'GET') {
        $controller->listar();
    } elseif ($method === 'POST') {
        $controller->guardar();
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
    }
    exit();
}

// 2. RUTA WEB PRINCIPAL (Carga la vista del Home)
require_once __DIR__ . '/vista/Home.php';