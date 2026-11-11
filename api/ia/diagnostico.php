<?php
/**
 * diagnostico.php — Prueba cada eslabón del chatbot y muestra dónde falla.
 * Abrir: http://localhost/api/diagnostico.php
 * BÓRRALO cuando termines (muestra datos internos).
 */
require __DIR__ . '/config.php';
require __DIR__ . '/prompts.php';
header('Content-Type: text/html; charset=utf-8');
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Solo local'); }
ini_set('display_errors', '1');
@ob_implicit_flush(true);

echo '<!DOCTYPE html><meta charset="utf-8"><title>Diagnóstico Respondin</title>
<style>body{font:15px Segoe UI,sans-serif;margin:24px;background:#10161d;color:#e8edf3}
table{border-collapse:collapse;width:100%}td{border-bottom:1px solid #2b3745;padding:8px;vertical-align:top}
tr.ok td:first-child{color:#5fd38d;font-weight:bold}tr.bad td:first-child{color:#ff8a80;font-weight:bold}
tr.bad{background:#2a1618}pre{margin:0;white-space:pre-wrap;word-break:break-word;font:13px Consolas,monospace}</style>
<h2>Diagnóstico de Respondin</h2><p>Cada fila es una prueba. Si una sale ✘, esa es la causa; lee el detalle.</p>
<table><tr><td></td><td><b>Prueba</b></td><td><b>Tiempo</b></td><td><b>Detalle</b></td></tr>';

function paso(string $nombre, callable $f): bool {
    $t = microtime(true);
    try { $d = $f(); $ok = true; } catch (Throwable $e) { $d = get_class($e) . ': ' . $e->getMessage(); $ok = false; }
    $seg = round(microtime(true) - $t, 2);
    $txt = is_string($d) ? $d : json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo '<tr class="' . ($ok ? 'ok' : 'bad') . '"><td>' . ($ok ? '✔' : '✘') . '</td><td>' . htmlspecialchars($nombre)
       . '</td><td>' . $seg . ' s</td><td><pre>' . htmlspecialchars((string)$txt) . '</pre></td></tr>';
    @ob_flush(); flush();
    return $ok;
}

// 1. PHP y extensiones
paso('1. PHP y extensiones', function () {
    $falta = [];
    foreach (['mbstring', 'pdo_mysql', 'json'] as $e) if (!extension_loaded($e)) $falta[] = $e;
    if ($falta) throw new RuntimeException('Faltan extensiones: ' . implode(', ', $falta));
    return ['php' => PHP_VERSION, 'sistema' => PHP_OS_FAMILY, 'curl_en_apache' => extension_loaded('curl') ? 'sí' : 'NO (se usa el respaldo sin curl)', 'allow_url_fopen' => ini_get('allow_url_fopen'), 'php_ini_que_usa_apache' => php_ini_loaded_file()];
});

// 2. Base de datos
$dbOk = paso('2. Conexión a MySQL', function () {
    return db()->query('SELECT DATABASE() AS base, VERSION() AS version')->fetch();
});

// 3. Tablas y columnas
if ($dbOk) paso('3. Tablas y columnas (schema.sql ejecutado)', function () {
    $pdo = db(); $falta = [];
    $need = [
        'reportes' => ['id','folio','ciudadano_id','titulo','descripcion','categoria','prioridad','latitud','longitud','foto_url','estatus','area_derivada','fecha_creacion'],
        'ciudadanos' => ['id','nombre','telefono'],
        'conversaciones' => ['id','session_id','datos','acepto_terminos','ip'],
        'mensajes' => ['id','conversacion_id','rol','contenido'],
        'historial_estatus' => ['id','reporte_id','estatus'],
        'notificaciones' => ['id','reporte_id','telefono','estatus','mensaje','enviado'],
    ];
    foreach ($need as $tabla => $cols) {
        $st = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
        $st->execute([DB_NAME, $tabla]);
        $tiene = array_column($st->fetchAll(), 'COLUMN_NAME');
        if (!$tiene) { $falta[] = "tabla $tabla NO existe"; continue; }
        $m = array_diff($cols, $tiene);
        if ($m) $falta[] = "$tabla: faltan columnas " . implode(', ', $m);
    }
    if ($falta) throw new RuntimeException(implode("\n", $falta));
    return 'Todas las tablas y columnas existen';
});

// 4. Escritura real en BD (se borra al terminar)
if ($dbOk) paso('4. Guardar un reporte de prueba en la BD', function () {
    $pdo = db(); $rid = $cid = null;
    try {
        $pdo->prepare('INSERT INTO ciudadanos (nombre, telefono) VALUES (?,?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)')
            ->execute(['Prueba', '0000000000']);
        $cid = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO reportes (folio, ciudadano_id, titulo, descripcion, categoria, prioridad, estatus) VALUES ('RC-TEST-0000',?,?,?,?,?,?)")
            ->execute([$cid, 'Prueba', 'Reporte de prueba', 'bache', 'media', 'reporte recibido']);
        $rid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO historial_estatus (reporte_id, estatus) VALUES (?,?)')->execute([$rid, 'reporte recibido']);
        $pdo->prepare('INSERT INTO notificaciones (reporte_id, telefono, estatus, mensaje) VALUES (?,?,?,?)')->execute([$rid, '0000000000', 'reporte recibido', 'prueba']);
        return "Se guardó (reporte id $rid) y ahora se borra la prueba";
    } finally {
        if ($rid) { $pdo->prepare('DELETE FROM notificaciones WHERE reporte_id=?')->execute([$rid]);
                    $pdo->prepare('DELETE FROM historial_estatus WHERE reporte_id=?')->execute([$rid]);
                    $pdo->prepare('DELETE FROM reportes WHERE id=?')->execute([$rid]); }
        if ($cid) $pdo->prepare('DELETE FROM ciudadanos WHERE id=?')->execute([$cid]);
    }
});

// 5. Ollama en línea y modelo instalado
$olOk = paso('5. Ollama en línea y modelo "' . OLLAMA_MODEL . '"', function () {
    if (!ollama_up()) throw new RuntimeException('Ollama no responde en ' . OLLAMA_URL);
    $r = json_decode((string)file_get_contents(OLLAMA_URL . '/api/tags'), true);
    $nombres = array_column($r['models'] ?? [], 'name');
    if (!in_array(OLLAMA_MODEL, $nombres, true)) {
        throw new RuntimeException('El modelo "' . OLLAMA_MODEL . '" no está instalado. Instalados: ' . implode(', ', $nombres));
    }
    return ['instalados' => $nombres];
});

// 6-7. Llamadas al modelo
if ($olOk) {
    $ok6 = paso('6. Mensaje simple a la IA', fn() => ollama_chat([['role' => 'user', 'content' => 'Responde solo: hola']]));

    paso('7. Prompt REAL de Respondin en modo JSON', function () {
        $raw = ollama_chat([
            ['role' => 'system', 'content' => prompt_sistema([], '')],
            ['role' => 'user', 'content' => 'Quiero reportar un bache en mi calle'],
        ], true);
        $j = json_decode($raw, true);
        if (!is_array($j) || empty($j['respuesta'])) throw new RuntimeException("La IA no devolvió el JSON esperado. Respuesta cruda:\n$raw");
        return ['json_valido' => true, 'respuesta' => $j['respuesta'], 'datos' => $j['datos'] ?? null];
    });

    // 8. Chatbot completo por HTTP (igual que la página)
    paso('8. chatbot.php completo vía Apache', function () {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/chatbot.php';
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n",
            'content' => json_encode(['mensaje' => 'Quiero reportar un bache en la calle Reforma', 'acepta_terminos' => true]),
            'timeout' => 120, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) throw new RuntimeException("No se pudo llamar a $url");
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
        $j = json_decode($body, true);
        if ($code !== 200 || isset($j['error'])) throw new RuntimeException("HTTP $code\n$body");
        return ['http' => $code, 'respuesta' => $j['respuesta'] ?? $body, 'faltan' => $j['faltan'] ?? null];
    });
}

// 9. Qué hay guardado
if ($dbOk) paso('9. Lo que hay guardado en la BD', function () {
    $pdo = db(); $o = [];
    foreach (['conversaciones', 'mensajes', 'ciudadanos', 'reportes', 'notificaciones'] as $t) $o['total_' . $t] = (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    $o['ultimos_mensajes'] = $pdo->query('SELECT rol, LEFT(contenido, 100) AS contenido FROM mensajes ORDER BY id DESC LIMIT 5')->fetchAll();
    $o['ultimos_reportes'] = $pdo->query('SELECT folio, titulo, categoria, estatus FROM reportes ORDER BY id DESC LIMIT 5')->fetchAll();
    return $o;
});

echo '</table><p>Al terminar, borra <code>diagnostico.php</code>.</p>';
