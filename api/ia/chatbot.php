<?php
/**
 * chatbot.php — API del chatbot "Respondin"
 *
 * POST (JSON o multipart/form-data):
 *   session_id        (vacío en el primer mensaje)
 *   acepta_terminos   true  (obligatorio al iniciar sesión)
 *   mensaje           texto del ciudadano
 *   latitud, longitud (opcional, GPS)
 *   foto              (opcional, archivo jpg/png/webp ≤ 5 MB)
 *
 * Respuesta: { session_id, respuesta, folio?, estatus?, emergencia?, faltan[] }
 */
require __DIR__ . '/config.php';
require __DIR__ . '/prompts.php';

function guardar_msg(PDO $pdo, int $convId, string $rol, string $texto): void {
    $pdo->prepare('INSERT INTO mensajes (conversacion_id, rol, contenido) VALUES (?,?,?)')
        ->execute([$convId, $rol, $texto]);
}

function categoria_por_palabras(string $t): ?string {
    $mapa = [
        'bache'     => '/bache|hoyo|socav/iu',
        'alumbrado' => '/luminaria|alumbrado|l[aá]mpara|poste de luz|sin luz/iu',
        'fuga_agua' => '/fuga|agua potable|tuber[ií]a|drenaje|inundaci|sin agua/iu',
        'basura'    => '/basura|tiradero|desecho|recolecci/iu',
        'seguridad' => '/inseguridad|vandal|robo|asalt/iu',
    ];
    foreach ($mapa as $cat => $re) if (preg_match($re, $t)) return $cat;
    return null;
}

function emergencia_por_palabras(string $t): ?string {
    if (preg_match('/herid|sangr|inconsciente|infarto|ahog|atropell/iu', $t)) return 'ambulancia';
    if (preg_match('/\b(incendio|fuego|humo|gas)\b|explosi/iu', $t)) return 'bomberos';
    if (preg_match('/balacera|disparo|me est[aá]n robando|asalt|secuestro/iu', $t)) return 'policia';
    return null;
}

/** Pregunta determinista (no depende del modelo): pide lo que falta o pide confirmar. */
function pregunta_siguiente(array $d, array $faltan): string {
    if (in_array('descripcion', $faltan, true)) return 'Cuéntame qué problema quieres reportar y en qué lugar está, o qué trámite necesitas consultar.';
    if (in_array('nombre', $faltan, true))      return '¿Cuál es tu nombre?';
    if (in_array('telefono', $faltan, true))    return '¿Cuál es tu número de WhatsApp (10 dígitos) para avisarte del avance?';
    $que = $d['titulo'] ?? mb_substr($d['descripcion'] ?? '', 0, 120);
    return "Resumen: $que. A nombre de {$d['nombre']}, WhatsApp {$d['telefono']}. ¿Confirmas que lo registre?";
}

function registrar_reporte(PDO $pdo, array $d): array {
    $cid = null;
    if (!empty($d['telefono'])) {
        $pdo->prepare('INSERT INTO ciudadanos (nombre, telefono) VALUES (?,?)
                       ON DUPLICATE KEY UPDATE nombre = COALESCE(VALUES(nombre), nombre), id = LAST_INSERT_ID(id)')
            ->execute([$d['nombre'] ?? null, $d['telefono']]);
        $cid = (int)$pdo->lastInsertId();
    }
    $desc = $d['descripcion'];
    if (!empty($d['referencia_ubicacion'])) $desc .= "\nUbicación indicada: " . $d['referencia_ubicacion'];
    $titulo = $d['titulo'] ?? mb_substr($d['descripcion'], 0, 80);
    $area   = $d['emergencia'] ?? null;
    $prio   = $area ? 'urgente' : ($d['prioridad'] ?? 'media');

    for ($i = 0; $i < 5; $i++) {
        $folio = 'RC-' . date('ymd') . '-' . random_int(1000, 9999);
        try {
            $pdo->prepare('INSERT INTO reportes
                (folio, ciudadano_id, titulo, descripcion, categoria, prioridad, latitud, longitud, foto_url, estatus, area_derivada)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$folio, $cid, $titulo, $desc, $d['categoria'] ?? 'otro', $prio,
                           $d['latitud'] ?? null, $d['longitud'] ?? null, $d['foto_url'] ?? null,
                           'reporte recibido', $area]);
            break;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;   // folio repetido → reintenta
        }
    }
    $rid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO historial_estatus (reporte_id, estatus) VALUES (?,?)')->execute([$rid, 'reporte recibido']);
    if (!empty($d['telefono'])) {
        $pdo->prepare('INSERT INTO notificaciones (reporte_id, telefono, estatus, mensaje) VALUES (?,?,?,?)')
            ->execute([$rid, $d['telefono'], 'reporte recibido', "Tu reporte $folio fue recibido. Te avisaremos cada cambio de estatus."]);
    }
    return ['id' => $rid, 'folio' => $folio];
}

try {
    $in      = entrada();
    $pdo     = db();
    $mensaje = trim((string)($in['mensaje'] ?? ''));
    $sid     = preg_replace('/[^a-zA-Z0-9]/', '', (string)($in['session_id'] ?? ''));
    $acepta  = filter_var($in['acepta_terminos'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // ---- Sesión ----
    $conv = null;
    if ($sid !== '') {
        $st = $pdo->prepare('SELECT * FROM conversaciones WHERE session_id = ?');
        $st->execute([$sid]);
        $conv = $st->fetch() ?: null;
    }
    if (!$conv) {
        if (!$acepta) json_out(['requiere_terminos' => true, 'respuesta' => AVISO_TERMINOS]);
        $sid = bin2hex(random_bytes(16));
        $pdo->prepare('INSERT INTO conversaciones (session_id, datos, acepto_terminos, ip) VALUES (?,?,1,?)')
            ->execute([$sid, '{}', $_SERVER['REMOTE_ADDR'] ?? null]);
        $conv = ['id' => (int)$pdo->lastInsertId(), 'datos' => '{}'];
        guardar_msg($pdo, $conv['id'], 'assistant', SALUDO);
        if ($mensaje === '' && empty($_FILES['foto'])) {
            json_out(['session_id' => $sid, 'respuesta' => SALUDO]);
        }
    }
    $convId = (int)$conv['id'];
    $datos  = json_decode($conv['datos'], true) ?: [];

    // ---- Adjuntos (foto / ubicación) ----
    $adjunto = '';
    if (!empty($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $f    = $_FILES['foto'];
        $info = @getimagesize($f['tmp_name']);
        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
        if ($ext && $f['size'] <= 5 * 1024 * 1024) {
            $nombre = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $nombre)) {
                $datos['foto_url'] = 'api/uploads/' . $nombre;
                $adjunto .= ' [El ciudadano adjuntó una foto]';
            }
        }
    }
    if (isset($in['latitud'], $in['longitud']) && is_numeric($in['latitud']) && is_numeric($in['longitud'])) {
        $datos['latitud']  = round((float)$in['latitud'], 7);
        $datos['longitud'] = round((float)$in['longitud'], 7);
        $adjunto .= ' [El ciudadano compartió su ubicación GPS]';
    }
    if ($mensaje === '' && $adjunto === '') json_out(['error' => 'Mensaje vacío'], 400);
    $mensaje = mb_substr($mensaje, 0, 1000);
    guardar_msg($pdo, $convId, 'user', trim($mensaje . $adjunto));

    // ---- Consulta de folio ----
    $contexto = '';
    if (preg_match('/RC-\d{6}-\d{4}/i', $mensaje, $m)) {
        $folio = strtoupper($m[0]);
        $st = $pdo->prepare('SELECT titulo, estatus, fecha_creacion FROM reportes WHERE folio = ?');
        $st->execute([$folio]);
        $r = $st->fetch();
        $contexto = $r ? "Folio $folio: \"{$r['titulo']}\", estatus actual: {$r['estatus']}, creado {$r['fecha_creacion']}."
                       : "El folio $folio no existe en el sistema.";
    }

    // ---- Historial reciente + IA ----
    $st = $pdo->prepare('SELECT rol, contenido FROM mensajes WHERE conversacion_id = ? ORDER BY id DESC LIMIT 12');
    $st->execute([$convId]);
    $hist = array_reverse($st->fetchAll());
    $msgs = [['role' => 'system', 'content' => prompt_sistema($datos, $contexto)]];
    foreach ($hist as $h) $msgs[] = ['role' => $h['rol'], 'content' => $h['contenido']];

    try {
        $raw = ollama_chat($msgs, true);
    } catch (Throwable $e) {
        $sos = preg_match('/herid|sangr|incendio|fuego|humo|balacera|disparo|explosi|\bgas\b|inconsciente|infarto|secuestro|asalt|me est[aá]n robando/iu', $mensaje)
            ? "🚨 Si hay personas en peligro, llama al 911 ahora mismo.\n" : '';
        json_out(['session_id' => $sid, 'respuesta' => $sos . 'Tengo un problema técnico en este momento. Intenta de nuevo en unos segundos.', 'error' => $e->getMessage()], 503);
    }
    $j         = json_decode($raw, true);
    $respuesta = is_array($j) && !empty($j['respuesta']) && is_string($j['respuesta']) ? trim($j['respuesta']) : '';
    if (!is_array($j) && $raw !== '' && $raw[0] !== '{' && $raw[0] !== '[') $respuesta = $raw;   // texto plano
    if ($respuesta !== '' && (mb_strlen($respuesta) < 3 || $respuesta[0] === '{')) $respuesta = '';   // {} o basura
    $nuevo     = is_array($j) && is_array($j['datos'] ?? null) ? $j['datos'] : [];

    // ---- Validar y mezclar datos ----
    foreach (['nombre', 'titulo', 'descripcion', 'referencia_ubicacion'] as $k) {
        if (!empty($nuevo[$k]) && is_string($nuevo[$k])) $datos[$k] = mb_substr(trim($nuevo[$k]), 0, 600);
    }
    if (!empty($nuevo['telefono'])) {
        $t = preg_replace('/\D/', '', (string)$nuevo['telefono']);
        if (strlen($t) >= 10 && strlen($t) <= 13) $datos['telefono'] = $t;
    }
    if (in_array($nuevo['categoria'] ?? null, CATEGORIAS, true))   $datos['categoria'] = $nuevo['categoria'];
    if (in_array($nuevo['prioridad'] ?? null, PRIORIDADES, true))  $datos['prioridad'] = $nuevo['prioridad'];
    if (in_array($nuevo['emergencia'] ?? null, ['policia', 'bomberos', 'ambulancia'], true)) {
        $datos['emergencia'] = $nuevo['emergencia'];
        $datos['prioridad']  = 'urgente';
    }
    // ---- Respaldo por reglas: los modelos chicos fallan en esto, así que PHP lo asegura ----
    $ultimoBot = '';
    foreach (array_reverse($hist) as $h) { if ($h['rol'] === 'assistant') { $ultimoBot = $h['contenido']; break; } }

    if (preg_match('/(?<!\d)(\d(?:[\s\-.]?\d){9,12})(?!\d)/', $mensaje, $m)) {
        $t = preg_replace('/\D/', '', $m[1]);
        if (strlen($t) >= 10 && strlen($t) <= 13) $datos['telefono'] = $t;
    }
    $cat      = categoria_por_palabras($mensaje);
    $sosArea  = emergencia_por_palabras($mensaje);
    if (empty($datos['nombre']) && !$cat && !$sosArea && preg_match('/nombre/iu', $ultimoBot)) {
        $limpio = trim(preg_replace('/^(me llamo|mi nombre es|soy)\s+/iu', '', $mensaje), " .,!");
        if (preg_match('/^[\p{L}\' -]{2,60}$/u', $limpio) && count(preg_split('/\s+/u', $limpio)) <= 4) {
            $datos['nombre'] = mb_convert_case($limpio, MB_CASE_TITLE, 'UTF-8');
        }
    }
    if (!empty($datos['descripcion']) && empty($datos['referencia_ubicacion']) && !$cat && !$sosArea
        && preg_match('/ubicaci|d[oó]nde|calle|direcci|referencia/iu', $ultimoBot) && mb_strlen($mensaje) <= 200) {
        $datos['referencia_ubicacion'] = $mensaje;   // la persona contestó dónde está
    }
    if ($cat && empty($datos['categoria']))   $datos['categoria']   = $cat;
    if ($cat && empty($datos['descripcion'])) $datos['descripcion'] = mb_substr($mensaje, 0, 600);
    if ($sosArea) {
        $datos['emergencia'] = $sosArea;
        $datos['prioridad']  = 'urgente';
        if (empty($datos['descripcion'])) $datos['descripcion'] = mb_substr($mensaje, 0, 600);
    }
    if (!empty($datos['descripcion']) && empty($datos['categoria'])) $datos['categoria'] = 'otro';

    // La confirmación la decide PHP: el bot preguntó "¿Confirmas…?" y la persona dijo que sí
    $confirmado = (bool)preg_match('/confirmas/iu', $ultimoBot)
        && (bool)preg_match('/^\s*(s[ií]|sip|claro|confirmo|correcto|ok|dale|adelante)([\s.,!]+.*)?$/iu', $mensaje);

    $faltan = [];
    foreach (['descripcion', 'nombre', 'telefono'] as $k) if (empty($datos[$k])) $faltan[] = $k;

    $out = ['session_id' => $sid];
    $emergencia = $datos['emergencia'] ?? null;

    if ($respuesta === '') {
        $respuesta = pregunta_siguiente($datos, $faltan);
    } elseif ($faltan === [] && !$confirmado && !$emergencia) {
        $respuesta = pregunta_siguiente($datos, []);   // PHP fuerza pedir la confirmación
    }

    // ---- ¿Registrar? (completo + confirmado, o emergencia con descripción) ----
    $registrar = !empty($datos['descripcion']) && (($faltan === [] && $confirmado) || $emergencia);
    if ($registrar) {
        if (empty($datos['categoria'])) $datos['categoria'] = $emergencia ? 'seguridad' : 'otro';
        $rep = registrar_reporte($pdo, $datos);
        $respuesta = "✅ Tu reporte quedó registrado con el folio {$rep['folio']}. Estatus: reporte recibido."
            . (!empty($datos['telefono']) ? " Te avisaremos por WhatsApp al {$datos['telefono']} en cada cambio." : '');
        $out += ['folio' => $rep['folio'], 'estatus' => 'reporte recibido'];
        // limpiar lo propio del reporte; se conservan nombre y teléfono
        foreach (['titulo','descripcion','categoria','prioridad','referencia_ubicacion','latitud','longitud','foto_url','emergencia'] as $k) unset($datos[$k]);
    }
    if ($emergencia) {
        $out['emergencia'] = $emergencia;
        $respuesta = "🚨 Esto parece una emergencia ($emergencia). Llama al 911 ahora mismo.\n" . $respuesta;
    }

    guardar_msg($pdo, $convId, 'assistant', $respuesta);
    $pdo->prepare('UPDATE conversaciones SET datos = ? WHERE id = ?')
        ->execute([json_encode($datos, JSON_UNESCAPED_UNICODE), $convId]);

    json_out($out + ['respuesta' => $respuesta, 'faltan' => $registrar ? [] : $faltan]);
} catch (Throwable $e) {
    json_out(['error' => 'Error interno', 'detalle' => $e->getMessage()], 500);
}
