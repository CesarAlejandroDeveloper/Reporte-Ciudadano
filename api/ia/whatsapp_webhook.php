<?php
/**
 * whatsapp_webhook.php — Webhook bidireccional de WhatsApp Business para Respondin
 * Ruta: C:\xampp\htdocs\api\ia\whatsapp_webhook.php
 */
require __DIR__ . '/config.php';

// 1. Verificación del Webhook con Meta (GET)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $mode      = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token     = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    if ($mode === 'subscribe' && $token === WA_VERIFY_TOKEN) {
        header('Content-Type: text/plain');
        http_response_code(200);
        echo $challenge;
        exit;
    }
    http_response_code(403);
    exit('Token de verificación inválido');
}

function despachar_notificaciones_pendientes(PDO $pdo): void {
    $st = $pdo->query('SELECT id, telefono, mensaje FROM notificaciones WHERE enviado = 0 LIMIT 10');
    foreach ($st->fetchAll() as $n) {
        $tel = preg_replace('/\D/', '', $n['telefono']);
        if (strlen($tel) === 10) $tel = '521' . $tel;
        if (wa_enviar_texto($tel, "🔔 *CiudadReporta Veracruz*\n" . $n['mensaje'])) {
            $pdo->prepare('UPDATE notificaciones SET enviado = 1 WHERE id = ?')->execute([$n['id']]);
        }
    }
}

function wa_descargar_media(string $mediaId, string $prefijo): ?string {
    $ch = curl_init('https://graph.facebook.com/v21.0/' . $mediaId);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
            'User-Agent: CiudadReportaBot/1.0'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resInfo = (string)curl_exec($ch);
    curl_close($ch);

    $info = json_decode($resInfo, true);
    if (empty($info['url'])) {
        error_log("WA Media Info Error: " . $resInfo);
        return null;
    }

    $ch2 = curl_init($info['url']);
    curl_setopt_array($ch2, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
            'User-Agent: CiudadReportaBot/1.0'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $bin  = curl_exec($ch2);
    $mime = (string)curl_getinfo($ch2, CURLINFO_CONTENT_TYPE);
    $code = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ((!$bin) or ($code !== 200)) {
        error_log("WA Media Download Error HTTP $code");
        return null;
    }

    $mapaExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'audio/ogg'  => 'ogg',
        'audio/mpeg' => 'mp3',
        'audio/mp4'  => 'm4a',
    ];
    $mimeLimpio = strtolower(trim(explode(';', $mime)[0]));
    $ext  = $mapaExt[$mimeLimpio] ?? ($prefijo === 'wa_voz' ? 'ogg' : 'jpg');
    $ruta = __DIR__ . '/uploads/' . $prefijo . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    file_put_contents($ruta, $bin);
    return $ruta;
}

// 2. Recepción de eventos POST de WhatsApp
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '', true);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

try {
    $pdo = db();
    despachar_notificaciones_pendientes($pdo);

    $msg = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
    if (!$msg) exit;

    $from  = preg_replace('/\D/', '', (string)($msg['from'] ?? ''));
    $tel10 = substr($from, -10);
    $sid   = 'wa' . $tel10;
    $tipo  = $msg['type'] ?? '';

    $st = $pdo->prepare('SELECT * FROM conversaciones WHERE session_id = ?');
    $st->execute([$sid]);
    $conv = $st->fetch();
    if ($conv) {
        $datosConv = json_decode($conv['datos'], true) ?: [];
        if (empty($datosConv['telefono'])) {
            $datosConv['telefono'] = $tel10;
            $pdo->prepare('UPDATE conversaciones SET datos = ? WHERE id = ?')
                ->execute([json_encode($datosConv, JSON_UNESCAPED_UNICODE), $conv['id']]);
        }
    }

    $camposPost = [
        'session_id'      => $sid,
        'acepta_terminos' => 'true',
        'mensaje'         => '',
    ];
    $esNotaDeVoz = false;
    $rutaFoto    = null;

    if ($tipo === 'text') {
        $camposPost['mensaje'] = trim((string)($msg['text']['body'] ?? ''));
    } elseif ((($tipo === 'audio') or ($tipo === 'voice')) && !empty($msg[$tipo]['id'])) {
        $rutaAudio = wa_descargar_media($msg[$tipo]['id'], 'wa_voz');
        if ($rutaAudio) {
            $textoEscuchado = transcribir_audio_local($rutaAudio);
            @unlink($rutaAudio);
            if ($textoEscuchado === '') {
                wa_enviar_texto($from, "🎤 No pude escuchar con claridad tu nota de voz. ¿Podrías grabarla de nuevo o escribir tu mensaje?");
                exit;
            }
            $camposPost['mensaje']       = $textoEscuchado;
            $camposPost['responder_voz'] = '1';
            $esNotaDeVoz = true;
        }
    } elseif ($tipo === 'location') {
        $camposPost['latitud']  = (string)($msg['location']['latitude'] ?? '');
        $camposPost['longitud'] = (string)($msg['location']['longitude'] ?? '');
    } elseif ($tipo === 'image' && !empty($msg['image']['id'])) {
        $rutaFoto = wa_descargar_media($msg['image']['id'], 'wa_foto');
        if ($rutaFoto) {
            $camposPost['foto'] = new CURLFile($rutaFoto, 'image/jpeg', basename($rutaFoto));
            if (!empty($msg['image']['caption'])) {
                $camposPost['mensaje'] = trim((string)$msg['image']['caption']);
            }
        }
    }

    $dirWeb     = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/ia/whatsapp_webhook.php')), '/');
    $urlChatbot = 'http://127.0.0.1' . $dirWeb . '/chatbot.php';

    $ch = curl_init($urlChatbot);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $camposPost,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 115,
    ]);
    $respRaw = curl_exec($ch);
    curl_close($ch);

    if ($rutaFoto && file_exists($rutaFoto)) {
        @unlink($rutaFoto);
    }

    $resp = json_decode((string)$respRaw, true);
    if ((!is_array($resp)) or empty($resp['respuesta'])) exit;

    $textoRespuesta = $resp['respuesta'];

    if (($resp['accion'] ?? '') === 'foto') {
        $textoRespuesta = "📷 Por favor, toma o adjunta una *foto* del problema directamente aquí en este chat de WhatsApp.";
    } elseif (($resp['accion'] ?? '') === 'ubicacion') {
        $textoRespuesta = "📍 Por último, envíame la *ubicación* usando el clip de adjuntar de WhatsApp (Ubicación), para registrar el punto exacto en Veracruz.";
    }

    wa_enviar_texto($from, $textoRespuesta);

    if ($esNotaDeVoz && !empty($resp['audio_archivo'])) {
        $rutaMp3 = __DIR__ . '/uploads/' . $resp['audio_archivo'];
        wa_enviar_nota_voz($from, $rutaMp3);
    }
} catch (Throwable $e) {
    error_log('whatsapp_webhook.php: ' . $e->getMessage());
}