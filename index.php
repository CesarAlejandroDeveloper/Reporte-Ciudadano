<?php
session_start();
require_once __DIR__ . '/modelo/config.php';
require_once __DIR__ . '/modelo/Conexion.php';
require_once __DIR__ . '/controlador/ReporteController.php';

aplicar_cors_seguro('GET, POST, PUT, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

if (strpos($requestUri, '/api/') !== false) {
    $conexion = new Conexion();
    $db = $conexion->obtenerConexion();
    $controller = new ReporteController($db);

    if (preg_match('#/api/login$#', $requestUri) && $method === 'POST') {
        $controller->loginAdmin();
    } elseif (preg_match('#/api/logout$#', $requestUri)) {
        $controller->logoutAdmin();
    } elseif (preg_match('#/api/session$#', $requestUri) && $method === 'GET') {
        $controller->verificarSesionAdmin();
    } elseif (preg_match('#/api/reportes$#', $requestUri) && $method === 'GET') {
        $controller->listar();
    } elseif (preg_match('#/api/reportes$#', $requestUri) && $method === 'POST') {
        $controller->guardar();
    } elseif (preg_match('#/api/reportes/estatus$#', $requestUri) && ($method === 'POST' || $method === 'PUT')) {
        $controller->actualizarEstatus();
    } else {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["error" => "Endpoint no encontrado"]);
    }
    exit();
}

header("Location: vista/Home.php");
exit();