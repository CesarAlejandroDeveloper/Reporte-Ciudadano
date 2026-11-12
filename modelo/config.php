<?php
// Configuración centralizada de CiudadReporta y APIs
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'reporte_ciudadano');
define('DB_USER', 'root');
define('DB_PASS', '');

// Token seguro para api/ia/estatus.php
define('API_ADMIN_TOKEN', 'cr_v3r4cruz_9f8a7b6c5d4e3f2a1b0c9d8e7f6a5b4c');

// Control de publicaciones por dispositivo y zona (habilitado para Mapa de Calor)
define('COOLDOWN_SEGUNDOS_DISPOSITIVO', 2);       // Espera mínima entre envíos (evita doble clic)
define('MAX_REPORTES_POR_HORA_DISPOSITIVO', 150);  // Publicaciones permitidas por hora desde un dispositivo
define('BLOQUEAR_DUPLICADOS_ZONA', false);        // false = permite acumular reportes en una misma zona

// Vocabulario unificado en todo el sistema
define('ESTATUS_VALIDOS', ['reporte recibido', 'en revision', 'en proceso', 'resuelto', 'denegada']);
define('CATEGORIAS_VALIDAS', ['bache', 'luminaria', 'fuga', 'basura', 'accidente', 'tramite', 'seguridad', 'otro']);
define('PRIORIDADES_VALIDAS', ['baja', 'media', 'alta', 'urgente']);

/**
 * Configura cabeceras CORS restringidas al host actual (localhost e IP local)
 */
function aplicar_cors_seguro(string $metodos = 'GET, POST, OPTIONS'): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $hostActual = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $origenesPermitidos = [
        'http://' . $hostActual,
        'https://' . $hostActual,
        'http://localhost',
        'http://127.0.0.1'
    ];

    if ($origin !== '' && in_array($origin, $origenesPermitidos, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');
    header('Access-Control-Allow-Methods: ' . $metodos);
}