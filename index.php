<?php
session_start();

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

// 1. RUTAS DE AUTENTICACIÓN ADMIN
if (strpos($uri, '/api/login') !== false) {
    if ($method === 'POST') {
        $controller->loginAdmin();
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
    }
    exit();
}

if (strpos($uri, '/api/logout') !== false) {
    $controller->logoutAdmin();
    exit();
}

if (strpos($uri, '/api/session') !== false) {
    $controller->verificarSesionAdmin();
    exit();
}

// 2. RUTAS DE ESTATUS Y REPORTES
if (strpos($uri, '/api/reportes/estatus') !== false) {
    if ($method === 'POST' || $method === 'PUT') {
        $controller->actualizarEstatus();
    } else {
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
    }
    exit();
}

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

// 3. VISTA PRINCIPAL POR DEFECTO
require_once __DIR__ . '/vista/Home.php';