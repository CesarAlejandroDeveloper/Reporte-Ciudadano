<?php
declare(strict_types=1);
/**
 * config.php — Configuración y funciones compartidas de la API
 * Ruta: C:\xampp\htdocs\api\ia\config.php
 */

// ====== AJUSTES DE OLLAMA Y BD ======
const OLLAMA_URL   = 'http://127.0.0.1:11434';
const OLLAMA_MODEL = 'llama3.2:1b';
const OLLAMA_EXE   = 'ollama';

const DB_HOST = '127.0.0.1';
const DB_NAME = 'reporte_ciudadano';
const DB_USER = 'root';
const DB_PASS = '';

const ADMIN_TOKEN = 'cambia-este-token';
const ESTATUS     = ['reporte recibido', 'en analisis', 'en atencion', 'resuelto'];
const CATEGORIAS  = ['bache', 'alumbrado', 'fuga_agua', 'basura', 'tramite', 'seguridad', 'otro'];
const PRIORIDADES = ['baja', 'media', 'alta', 'urgente'];

const AVISO_TERMINOS = 'Esta conversación se guarda con fines de seguridad y seguimiento. '
    . 'Al continuar aceptas los términos y condiciones del servicio.';

// ====== AJUSTES DE VOZ (STT/TTS) Y WHATSAPP BUSINESS ======
const VOZ_URL            = 'http://127.0.0.1:5005';
const WA_VERIFY_TOKEN    = 'respondin_veracruz_2026';
const WA_PHONE_NUMBER_ID = '1477953158728756';
const WA_ACCESS_TOKEN    = 'EAAZAaqDZAPL10BS6ZB7D5KcwIXlswfaD5LCEheEjosz8j4iHbYObom00gGNqtDE4o3mFjVF9EtTEtQzoVZAZC1ZB84IalMYuz2OLerefyY8W9lyZAC62F4pg1qZAqP3HIWE0f9hkZAU9j0SIWeH8FtHZCdUpCsO3qfjonliWyvnRYjfXmK1jxznKjsWPHJvnTAkNWZCJsvbHo9ZBttKEyZBbZCHs1ona4XZC43sV2zEmRBOcgW7wp1ZBZCLXhfSW1KMisXyKJSRJj8JcJ4mXZBE4aeM9YhOpQFphDr';

// ====== Cabeceras / utilidades ======
set_time_limit(120);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
    exit;
}

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
    if (($code !== 200) or (!is_array($d))) {
        throw new RuntimeException($d['error'] ?? "Ollama respondió HTTP $code");
    }
    return $d;
}

function ollama_chat(array $messages, bool $json = false, ?string $model = null): string {
    $intentos = [
        ['temperature' => 0.2, 'repeat_penalty' => 1.05],
        ['temperature' => 0.6, 'repeat_penalty' => 1.2],
    ];
    $ultimo = null;
    foreach ($intentos as $opt) {
        $body = [
            'model'      => $model ?: OLLAMA_MODEL,
            'messages'   => $messages,
            'stream'     => false,
            'keep_alive' => '30m',
            'options'    => $opt + ['num_predict' => 350, 'num_ctx' => 4096],
        ];
        if ($json) $body['format'] = 'json';
        try {
            $d = ollama_post('/api/chat', $body);
            return trim($d['message']['content'] ?? '');
        } catch (RuntimeException $e) {
            $ultimo = $e;
            $msgErr = $e->getMessage();
            if (!preg_match('/repeat/i', $msgErr) && !preg_match('/aborted/i', $msgErr)) break;
        }
    }
    throw $ultimo;
}

// ====== Funciones de Voz (STT y TTS) ======
function transcribir_audio_local(string $rutaArchivo): string {
    if (!file_exists($rutaArchivo)) return '';
    $rutaReal = realpath($rutaArchivo);
    $payload = json_encode(['ruta' => $rutaReal ? $rutaReal : $rutaArchivo], JSON_UNESCAPED_UNICODE);
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\n",
        'content'       => $payload,
        'timeout'       => 60,
        'ignore_errors' => true,
    ]]);
    $res = @file_get_contents(VOZ_URL . '/stt_local', false, $ctx);
    if ($res === false) return '';
    $d = json_decode($res, true);
    return trim((string)($d['texto'] ?? ''));
}

function generar_tts_local(string $texto): ?array {
    if (trim($texto) === '') return null;
    $payload = json_encode(['texto' => $texto], JSON_UNESCAPED_UNICODE);
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\n",
        'content'       => $payload,
        'timeout'       => 30,
        'ignore_errors' => true,
    ]]);
    $res = @file_get_contents(VOZ_URL . '/tts', false, $ctx);
    if ($res === false) return null;
    $d = json_decode($res, true);
    return (!empty($d['ok']) && !empty($d['archivo'])) ? $d : null;
}

// ====== Funciones de WhatsApp Business Cloud API ======
function wa_enviar_texto(string $telefono, string $texto): bool {
    if ((WA_ACCESS_TOKEN === '') or (WA_PHONE_NUMBER_ID === '')) return false;
    $url = 'https://graph.facebook.com/v21.0/' . WA_PHONE_NUMBER_ID . '/messages';
    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to'                => $telefono,
        'type'              => 'text',
        'text'              => ['body' => $texto]
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
            'Content-Type: application/json',
            'User-Agent: CiudadReportaBot/1.0'
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $res = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (($code < 200) or ($code >= 300)) {
        error_log("WA Enviar Texto HTTP $code: $res");
    }
    return ($code >= 200) && ($code < 300);
}

function wa_enviar_nota_voz(string $telefono, string $rutaMp3Local): bool {
    if ((!file_exists($rutaMp3Local)) or (WA_ACCESS_TOKEN === '')) return false;

    $ch = curl_init('https://graph.facebook.com/v21.0/' . WA_PHONE_NUMBER_ID . '/media');
    $cfile = new CURLFile($rutaMp3Local, 'audio/mpeg', basename($rutaMp3Local));
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
            'User-Agent: CiudadReportaBot/1.0'
        ],
        CURLOPT_POSTFIELDS     => [
            'messaging_product' => 'whatsapp',
            'file'              => $cfile,
            'type'              => 'audio/mpeg'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resMedia = json_decode((string)curl_exec($ch), true);
    curl_close($ch);

    $mediaId = $resMedia['id'] ?? null;
    if (!$mediaId) return false;

    $ch2 = curl_init('https://graph.facebook.com/v21.0/' . WA_PHONE_NUMBER_ID . '/messages');
    curl_setopt_array($ch2, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
            'Content-Type: application/json',
            'User-Agent: CiudadReportaBot/1.0'
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            'messaging_product' => 'whatsapp',
            'to'                => $telefono,
            'type'              => 'audio',
            'audio'             => ['id' => $mediaId]
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 20,
    ]);
    curl_exec($ch2);
    $code = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    return ($code >= 200) && ($code < 300);
}