<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['audio'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ningún archivo de audio.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$archivo = $_FILES['audio'];
if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Archivo de audio inválido o mayor a 10 MB.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmpDir = sys_get_temp_dir();
$ext = pathinfo($archivo['name'], PATHINFO_EXTENSION) ?: 'ogg';
$ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
$rutaAudio = $tmpDir . DIRECTORY_SEPARATOR . uniqid('wa_audio_') . '.' . $ext;

if (!move_uploaded_file($archivo['tmp_name'], $rutaAudio)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo procesar el audio temporal.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Intentar transcripción local con Whisper CLI si está instalado en el sistema
$cmd = sprintf(
    'whisper %s --language Spanish --model tiny --output_format txt --output_dir %s 2>&1',
    escapeshellarg($rutaAudio),
    escapeshellarg($tmpDir)
);
@exec($cmd, $salida, $codigo);

$rutaTxt = preg_replace('/\.[^.]+$/', '.txt', $rutaAudio);
$texto = '';
if ($codigo === 0 && is_file($rutaTxt)) {
    $texto = trim((string)file_get_contents($rutaTxt));
    @unlink($rutaTxt);
}
@unlink($rutaAudio);

if ($texto !== '') {
    echo json_encode(['texto' => $texto], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(422);
    echo json_encode([
        'error' => 'Whisper local no está instalado; usa el botón de nota de voz en vivo (🎙️) para hablar directo.'
    ], JSON_UNESCAPED_UNICODE);
}