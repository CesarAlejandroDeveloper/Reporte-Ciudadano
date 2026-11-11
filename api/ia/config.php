<?php
declare(strict_types=1);
/**
 * config.php — Configuración y funciones compartidas de la API
 * Ruta: C:\xampp\htdocs\api\config.php
 */

// ====== AJUSTES (edita aquí) ======
const OLLAMA_URL   = 'http://127.0.0.1:11434';
const OLLAMA_MODEL = 'llama3.2:1b'; // el modelo que tengas: `ollama list`
// Si Apache no encuentra "ollama", pon la ruta completa, p. ej.:
// 'C:\\Users\\TU_USUARIO\\AppData\\Local\\Programs\\Ollama\\ollama.exe'
const OLLAMA_EXE   = 'ollama';

const DB_HOST = '127.0.0.1';
const DB_NAME = 'reporte_ciudadano';
const DB_USER = 'root';
const DB_PASS = '';

const ADMIN_TOKEN = 'cambia-este-token';   // para cambiar estatus (estatus.php)

const ESTATUS     = ['reporte recibido', 'en analisis', 'en atencion', 'resuelto'];
const CATEGORIAS  = ['bache', 'alumbrado', 'fuga_agua', 'basura', 'tramite', 'seguridad', 'otro'];
const PRIORIDADES = ['baja', 'media', 'alta', 'urgente'];

const AVISO_TERMINOS = 'Esta conversación se guarda con fines de seguridad y seguimiento. '
    . 'Al continuar aceptas los términos y condiciones del servicio.';

// ====== Cabeceras / utilidades ======
set_time_limit(120);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');   // en producción limita al dominio que consuma la API
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Lee JSON del body o campos de formulario (multipart / x-www-form-urlencoded). */
function entrada(): array {
    $json = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

// ====== Ollama ======
function ollama_up(): bool {
    $p = parse_url(OLLAMA_URL);
    $s = @fsockopen($p['host'], $p['port'], $errno, $errstr, 0.5);
    if ($s) { fclose($s); return true; }
    return false;
}

/** Si Ollama está apagado lo enciende y espera hasta 10 s. */
function ollama_asegurar(): bool {
    if (ollama_up()) return true;
    if (stripos(PHP_OS_FAMILY, 'Windows') === 0) {
        pclose(popen('start /B "" "' . OLLAMA_EXE . '" serve', 'r'));
    } else {
        exec('nohup ' . escapeshellarg(OLLAMA_EXE) . ' serve > /dev/null 2>&1 &');
    }
    for ($i = 0; $i < 20; $i++) {
        usleep(500000);
        if (ollama_up()) return true;
    }
    return false;
}

function ollama_post(string $ruta, array $body): array {
    if (!ollama_asegurar()) throw new RuntimeException('Ollama no está disponible');
    $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
    if ($payload === false) throw new RuntimeException('No se pudo codificar JSON: ' . json_last_error_msg());

    if (function_exists('curl_init')) {
        $ch = curl_init(OLLAMA_URL . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 110,
        ]);
        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($res === false) throw new RuntimeException($err ?: 'Sin respuesta de Ollama');
    } else {
        // Respaldo si Apache no tiene la extensión curl: usa streams de PHP
        $ctx = stream_context_create(['http' => [
            'method' => 'POST', 'header' => "Content-Type: application/json\r\n",
            'content' => $payload, 'timeout' => 110, 'ignore_errors' => true,
        ]]);
        $res = @file_get_contents(OLLAMA_URL . $ruta, false, $ctx);
        if ($res === false) throw new RuntimeException('Sin respuesta de Ollama (sin curl)');
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
    }
    $d = json_decode($res, true);
    if ($code !== 200 || !is_array($d)) throw new RuntimeException($d['error'] ?? "Ollama respondió HTTP $code");
    return $d;
}

/** Chat con Ollama. $json=true fuerza salida JSON válida. Reintenta si el modelo se cicla. */
function ollama_chat(array $messages, bool $json = false, ?string $model = null): string {
    $intentos = [
        ['temperature' => 0.2, 'repeat_penalty' => 1.05],
        ['temperature' => 0.6, 'repeat_penalty' => 1.2],   // 2.º intento si se ciclaba
    ];
    $ultimo = null;
    foreach ($intentos as $opt) {
        $body = [
            'model'      => $model ?: OLLAMA_MODEL,
            'messages'   => $messages,
            'stream'     => false,
            'keep_alive' => '30m',                       // modelo en memoria = respuestas rápidas
            'options'    => $opt + ['num_predict' => 350, 'num_ctx' => 4096],
        ];
        if ($json) $body['format'] = 'json';
        try {
            $d = ollama_post('/api/chat', $body);
            return trim($d['message']['content'] ?? '');
        } catch (RuntimeException $e) {
            $ultimo = $e;
            if (!preg_match('/repeat|aborted/i', $e->getMessage())) break;   // solo reintenta ese error
        }
    }
    throw $ultimo;
}
