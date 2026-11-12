<?php
/**
 * chatbot.php — API del chatbot "Respondin" (Texto + Notas de Voz STT/TTS + WhatsApp)
 * Ruta: C:\xampp\htdocs\api\ia\chatbot.php
 */
require __DIR__ . '/config.php';
require __DIR__ . '/prompts.php';

function guardar_msg(PDO $pdo, int $convId, string $rol, string $texto): void {
    $pdo->prepare('INSERT INTO mensajes (conversacion_id, rol, contenido) VALUES (?,?,?)')
        ->execute([$convId, $rol, $texto]);
}

function categoria_por_palabras(string $t): ?string {
    $mapa = [
        'bache'     => '/bache|hoyo|socav|hundimiento/iu',
        'alumbrado' => '/luminaria|alumbrado|l[aá]mpara|poste de luz|sin luz|farol/iu',
        'fuga_agua' => '/fuga|agua potable|tuber[ií]a|drenaje|inundaci|sin agua|alcanta/iu',
        'basura'    => '/basura|tiradero|desecho|recolecci/iu',
        'seguridad' => '/inseguridad|vandal|robo|asalt/iu',
    ];
    foreach ($mapa as $cat => $re) {
        if (preg_match($re, $t)) return $cat;
    }
    return null;
}

function emergencia_por_palabras(string $t): ?string {
    $reAmb = '/herid|sangr|inconsciente|infarto|ahog|atropell/iu';
    $reBom = '/\b(incendio|fuego|humo|gas)\b|explosi/iu';
    $rePol = '/balacera|disparo|me est[aá]n robando|asalt|secuestro/iu';
    if (preg_match($reAmb, $t)) return 'ambulancia';
    if (preg_match($reBom, $t)) return 'bomberos';
    if (preg_match($rePol, $t)) return 'policia';
    return null;
}

function faltantes(array $d): array {
    $f = [];
    if (empty($d['descripcion'])) $f[] = 'descripcion';
    if (empty($d['nombre']))      $f[] = 'nombre';
    if (empty($d['telefono']))    $f[] = 'telefono';
    if (empty($d['foto_url']))    $f[] = 'foto';
    if (!isset($d['latitud'], $d['longitud'])) $f[] = 'ubicacion';
    return $f;
}

function accion_de(array $faltan): ?string {
    $p = $faltan[0] ?? 'confirmar';
    return in_array($p, ['foto', 'ubicacion', 'confirmar'], true) ? $p : null;
}

function pregunta_siguiente(array $d, array $faltan): string {
    switch ($faltan[0] ?? '') {
        case 'descripcion': return 'Cuéntame qué problema quieres reportar (por ejemplo: bache, luminaria apagada, fuga de agua o basura) y en qué calle está.';
        case 'nombre':      return 'Perfecto, ya tomé nota del problema. ¿Cuál es tu nombre?';
        case 'telefono':    return ($d['nombre'] ? "Mucho gusto, {$d['nombre']}. " : '')
                                 . '¿Cuál es tu número de WhatsApp (10 dígitos) para darte seguimiento?';
        case 'foto':        return 'Ahora necesito una foto como evidencia. Usa el botón de abajo para tomarla o subirla desde tu dispositivo.';
        case 'ubicacion':   return 'Por último, indícame dónde está el problema. Usa el botón de abajo para elegir la ubicación en el mapa o usar tu ubicación actual.';
    }
    $que = $d['titulo'] ?? mb_substr($d['descripcion'] ?? '', 0, 120);
    return "Resumen: $que. A nombre de {$d['nombre']}, WhatsApp {$d['telefono']}, con foto y ubicación en el mapa. ¿Confirmas que lo registre?";
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
    if (!empty($d['referencia_ubicacion'])) {
        $desc .= "\nUbicación indicada: " . $d['referencia_ubicacion'];
    }
    $titulo = $d['titulo'] ?? mb_substr($d['descripcion'], 0, 80);
    $area   = $d['emergencia'] ?? null;
    $prio   = $area ? 'urgente' : ($d['prioridad'] ?? 'media');

    for ($i = 0; $i < 5; $i++) {
        $folio = 'RC-' . date('ymd') . '-' . random_int(1000, 9999);
        try {
            $pdo->prepare('INSERT INTO reportes
                (folio, ciudadano_id, titulo, descripcion, categoria, prioridad, latitud, longitud, foto_url, estatus, area_derivada)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([
                    $folio, $cid, $titulo, $desc, $d['categoria'] ?? 'otro', $prio,
                    $d['latitud'] ?? null, $d['longitud'] ?? null, $d['foto_url'] ?? null,
                    'reporte recibido', $area
                ]);
            break;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;
        }
    }
    $rid = (int)$pdo->lastInsertId();

    $pdo->prepare('INSERT INTO historial_estatus (reporte_id, estatus) VALUES (?,?)')
        ->execute([$rid, 'reporte recibido']);

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

    // ---- Procesar Nota de Voz (STT) si se envió audio ----
    $responderConVoz = !empty($in['responder_voz']);
    $textoTranscrito = '';
    if (!empty($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        $responderConVoz = true;
        $extAudio = pathinfo($_FILES['audio']['name'] ?? 'nota.webm', PATHINFO_EXTENSION);
        if (!$extAudio) $extAudio = 'webm';
        $rutaAudioTemp = __DIR__ . '/uploads/in_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $extAudio;
        if (move_uploaded_file($_FILES['audio']['tmp_name'], $rutaAudioTemp)) {
            $textoTranscrito = transcribir_audio_local($rutaAudioTemp);
            @unlink($rutaAudioTemp);
            if ($textoTranscrito !== '') {
                $mensaje = $textoTranscrito;
            } else {
                json_out(['session_id' => $sid, 'error' => 'No pude escuchar bien el audio. ¿Puedes grabarlo de nuevo?'], 400);
            }
        }
    }

    // ---- Sesión ----
    $conv = null;
    if ($sid !== '') {
        $st = $pdo->prepare('SELECT * FROM conversaciones WHERE session_id = ?');
        $st->execute([$sid]);
        $conv = $st->fetch() ?: null;
    }

    // ---- Retomar conversación ----
    if (($in['accion'] ?? '') === 'historial') {
        if (!$conv) json_out(['existe' => false]);
        $d  = json_decode($conv['datos'], true) ?: [];
        $st = $pdo->prepare('SELECT rol, contenido FROM (SELECT id, rol, contenido FROM mensajes
                             WHERE conversacion_id = ? ORDER BY id DESC LIMIT 50) t ORDER BY id');
        $st->execute([(int)$conv['id']]);
        $enFlujoHist = (!empty($d['modo_reporte']) or !empty($d['descripcion']));
        json_out([
            'existe'     => true,
            'session_id' => $sid,
            'mensajes'   => $st->fetchAll(),
            'accion'     => $enFlujoHist ? accion_de(faltantes($d)) : null
        ]);
    }

    if (!$conv) {
        if ($sid === '') {
            $sid = bin2hex(random_bytes(16));
        }
        $pdo->prepare('INSERT INTO conversaciones (session_id, datos, acepto_terminos, ip) VALUES (?,?,1,?)')
            ->execute([$sid, '{}', $_SERVER['REMOTE_ADDR'] ?? null]);
        $conv = ['id' => (int)$pdo->lastInsertId(), 'datos' => '{}'];
        guardar_msg($pdo, $conv['id'], 'assistant', SALUDO);
        if ($mensaje === '' && empty($_FILES['foto']) && !isset($in['latitud'])) {
            json_out(['session_id' => $sid, 'respuesta' => SALUDO]);
        }
    }

    $convId = (int)$conv['id'];
    $datos  = json_decode($conv['datos'], true) ?: [];

    // ---- Adjuntos (foto / ubicación del mapa) ----
    $adjunto = '';
    if (!empty($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $f    = $_FILES['foto'];
        $info = @getimagesize($f['tmp_name']);
        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime'] ?? ''] ?? null;
        if ($ext && $f['size'] <= 5 * 1024 * 1024) {
            $nombre = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $nombre)) {
                $datos['foto_url']     = 'api/ia/uploads/' . $nombre;
                $datos['modo_reporte'] = true;
                $adjunto .= '[El ciudadano adjuntó una foto]';
            }
        }
    }
    if (isset($in['latitud'], $in['longitud']) && is_numeric($in['latitud']) && is_numeric($in['longitud'])
        && abs((float)$in['latitud']) <= 90 && abs((float)$in['longitud']) <= 180) {
        $datos['latitud']      = round((float)$in['latitud'], 7);
        $datos['longitud']     = round((float)$in['longitud'], 7);
        $datos['modo_reporte'] = true;
        $adjunto .= ($adjunto ? ' ' : '') . '[El ciudadano eligió la ubicación en el mapa]';
    }

    if ($mensaje === '' && $adjunto === '') json_out(['error' => 'Mensaje vacío'], 400);
    $soloAdjunto = ($mensaje === '');
    $mensaje     = mb_substr($mensaje, 0, 1000);
    guardar_msg($pdo, $convId, 'user', trim($mensaje . ' ' . $adjunto));

    // ---- Cancelar reporte o cambiar foto/ubicación ----
    $reCancelar = '/^\s*(cancelar|olv[ií]dalo|ya no quiero reportar|salir del reporte)\b/iu';
    if (preg_match($reCancelar, $mensaje)) {
        foreach (['modo_reporte','titulo','descripcion','categoria','prioridad','referencia_ubicacion','latitud','longitud','foto_url','emergencia'] as $k) {
            unset($datos[$k]);
        }
        $respCancel = 'De acuerdo, he cancelado el reporte en curso. ¿En qué más te puedo ayudar?';
        guardar_msg($pdo, $convId, 'assistant', $respCancel);
        $pdo->prepare('UPDATE conversaciones SET datos = ? WHERE id = ?')->execute([json_encode($datos, JSON_UNESCAPED_UNICODE), $convId]);
        $outCancel = ['session_id' => $sid, 'respuesta' => $respCancel, 'accion' => null, 'faltan' => []];
        if ($textoTranscrito !== '') $outCancel['transcripcion'] = $textoTranscrito;
        if ($responderConVoz) {
            $ttsData = generar_tts_local($respCancel);
            if ($ttsData) {
                $outCancel['audio_url']     = $ttsData['url_relativa'];
                $outCancel['audio_archivo'] = $ttsData['archivo'];
            }
        }
        json_out($outCancel);
    }
    $reCambiarFoto = '/(otra|cambiar|corregir|nueva).{0,20}(foto|imagen)/iu';
    $reCambiarUbic = '/(otra|cambiar|corregir|nueva).{0,20}(ubicaci|mapa)/iu';
    if (preg_match($reCambiarFoto, $mensaje)) unset($datos['foto_url']);
    if (preg_match($reCambiarUbic, $mensaje)) unset($datos['latitud'], $datos['longitud']);

    // ---- Consulta de folio en la BD ----
    $contexto     = '';
    $infoFolioTxt = '';
    if (preg_match('/RC-\d{6}-\d{4}/i', $mensaje, $m)) {
        $folio = strtoupper($m[0]);
        $st = $pdo->prepare('SELECT id, titulo, categoria, prioridad, estatus, fecha_creacion FROM reportes WHERE folio = ?');
        $st->execute([$folio]);
        $r = $st->fetch();
        if ($r) {
            $sth = $pdo->prepare('SELECT estatus, creado FROM historial_estatus WHERE reporte_id = ? ORDER BY id ASC');
            $sth->execute([(int)$r['id']]);
            $pasos = [];
            foreach ($sth->fetchAll() as $h) {
                $pasos[] = "{$h['estatus']} ({$h['creado']})";
            }
            $histStr      = $pasos ? implode(' -> ', $pasos) : $r['estatus'];
            $infoFolioTxt = "Folio $folio: \"{$r['titulo']}\" (Categoría: {$r['categoria']}, Prioridad: {$r['prioridad']}).\n"
                          . "Estatus actual: {$r['estatus']} (Registrado el {$r['fecha_creacion']}).\n"
                          . "Historial: $histStr.";
            $contexto     = "Folio $folio: \"{$r['titulo']}\", categoría: {$r['categoria']}, prioridad: {$r['prioridad']}, estatus actual: {$r['estatus']}, creado: {$r['fecha_creacion']}, historial: $histStr.";
        } else {
            $infoFolioTxt = "El folio $folio no existe en el sistema. Verifica que esté bien escrito (ejemplo: RC-261112-1234).";
            $contexto     = "El folio $folio no existe en el sistema.";
        }
    }

    // ---- Historial de toda la conversación actual ----
    $st = $pdo->prepare('SELECT rol, contenido FROM (SELECT id, rol, contenido FROM mensajes WHERE conversacion_id = ? ORDER BY id DESC LIMIT 30) t ORDER BY id ASC');
    $st->execute([$convId]);
    $hist = $st->fetchAll();

    $ultimoBot = '';
    for ($i = count($hist) - 2; $i >= 0; $i--) {
        if ($hist[$i]['rol'] === 'assistant') {
            $ultimoBot = $hist[$i]['contenido'];
            break;
        }
    }

    $respuesta      = '';
    $nuevo          = [];
    $quiereReportar = (!empty($datos['modo_reporte']) or !empty($datos['descripcion']));

    if (!$soloAdjunto) {
        $msgs = [['role' => 'system', 'content' => prompt_sistema($datos, $contexto, $mensaje)]];
        foreach ($hist as $h) {
            $msgs[] = ['role' => $h['rol'], 'content' => $h['contenido']];
        }

        try {
            $raw = ollama_chat($msgs, true);
        } catch (Throwable $e) {
            error_log('chatbot.php (Ollama): ' . $e->getMessage());
            if ($infoFolioTxt !== '') {
                guardar_msg($pdo, $convId, 'assistant', $infoFolioTxt);
                json_out(['session_id' => $sid, 'respuesta' => $infoFolioTxt]);
            }
            $sos = emergencia_por_palabras($mensaje) ? "⚠️ Si hay personas en peligro, llama al 911 ahora mismo.\n" : '';
            json_out(['session_id' => $sid, 'respuesta' => $sos . 'Tengo un problema técnico en este momento. Intenta de nuevo en unos segundos.'], 503);
        }

        $j = json_decode($raw, true);
        if (is_array($j)) {
            $respuesta = !empty($j['respuesta']) && is_string($j['respuesta']) ? trim($j['respuesta']) : '';
            if (!empty($j['quiere_reportar'])) $quiereReportar = true;
            $nuevo = is_array($j['datos'] ?? null) ? $j['datos'] : [];
        } elseif ($raw !== '' && $raw[0] !== '{' && $raw[0] !== '[') {
            $respuesta = trim($raw);
        }
        if ($respuesta !== '' && ((mb_strlen($respuesta) < 3) or ($respuesta[0] === '{'))) {
            $respuesta = '';
        }
    }

    // ---- Detectar intención y validar datos ----
    $rePregunta = '/^\s*(hola|buen[oa]s|gracias|ayuda|qui[eé]n(es)? (eres|son|somos)|c[oó]mo|qu[eé]|cu[aá]ndo|d[oó]nde|por qu[eé]|para qu[eé]|informaci[oó]n)\b/iu';
    $esPregunta = ($mensaje !== '') && (
        str_contains($mensaje, '?') or str_contains($mensaje, '¿') or (bool)preg_match($rePregunta, $mensaje)
    );
    $reRep = '/(quiero|necesito|deseo|vengo a|me ayudas a|puedes|vamos a)\s+(hacer|levantar|registrar|crear|poner|enviar)?\s*(un\s+)?report(e|ar)|reportar\s+(un|una|el|la)/iu';
    $pideReportarExplicito = (bool)preg_match($reRep, $mensaje);
    $cat     = categoria_por_palabras($mensaje);
    $sosArea = emergencia_por_palabras($mensaje);

    if ($pideReportarExplicito or $sosArea or ($cat && !$esPregunta && $infoFolioTxt === '')) {
        $quiereReportar = true;
    }

    if ($quiereReportar) {
        $datos['modo_reporte'] = true;

        $mapCat = ['luminaria' => 'alumbrado', 'fuga' => 'fuga_agua'];
        if (!empty($nuevo['categoria']) && isset($mapCat[$nuevo['categoria']])) {
            $nuevo['categoria'] = $mapCat[$nuevo['categoria']];
        }
        foreach (['nombre', 'titulo', 'descripcion', 'referencia_ubicacion'] as $k) {
            if (!empty($nuevo[$k]) && is_string($nuevo[$k])) {
                $datos[$k] = mb_substr(trim($nuevo[$k]), 0, 600);
            }
        }
        $reTit = '/asistente|respondin|ciudad (que responde|reporta)/iu';
        if (!empty($datos['titulo']) && preg_match($reTit, $datos['titulo'])) {
            unset($datos['titulo']);
        }
        if (!empty($nuevo['telefono'])) {
            $t = preg_replace('/\D/', '', (string)$nuevo['telefono']);
            if (strlen($t) >= 10 && strlen($t) <= 13) $datos['telefono'] = substr($t, -10);
        }
        if (in_array($nuevo['categoria'] ?? null, CATEGORIAS, true))  $datos['categoria'] = $nuevo['categoria'];
        if (in_array($nuevo['prioridad'] ?? null, PRIORIDADES, true)) $datos['prioridad'] = $nuevo['prioridad'];
        if (in_array($nuevo['emergencia'] ?? null, ['policia', 'bomberos', 'ambulancia'], true)) {
            $datos['emergencia'] = $nuevo['emergencia'];
        }

        if (preg_match('/(?<!\d)(\d(?:[\s\-.]?\d){9,12})(?!\d)/', $mensaje, $m)) {
            $t = preg_replace('/\D/', '', $m[1]);
            if (strlen($t) >= 10 && strlen($t) <= 13) $datos['telefono'] = substr($t, -10);
        }

        $reMiNombre = '/^(hola\s*,?\s*)?(me llamo|mi nombre es|soy)\s+/iu';
        if (empty($datos['nombre']) && !$cat && !$sosArea && !$esPregunta && preg_match('/nombre/iu', $ultimoBot)) {
            $limpio = trim(preg_replace($reMiNombre, '', $mensaje), " .,!");
            if (preg_match('/^[\p{L}\' -]{2,60}$/u', $limpio) && count(preg_split('/\s+/u', $limpio)) <= 4) {
                $datos['nombre'] = mb_convert_case($limpio, MB_CASE_TITLE, 'UTF-8');
            }
        }
        $reUbic = '/ubicaci|d[oó]nde|calle|direcci|referencia/iu';
        if (!empty($datos['descripcion']) && empty($datos['referencia_ubicacion']) && !$cat && !$sosArea
            && preg_match($reUbic, $ultimoBot) && mb_strlen($mensaje) <= 200) {
            $datos['referencia_ubicacion'] = $mensaje;
        }

        if ($cat && empty($datos['categoria']))   $datos['categoria']   = $cat;
        if ($cat && empty($datos['descripcion'])) $datos['descripcion'] = mb_substr($mensaje, 0, 600);
        if ($sosArea) {
            $datos['emergencia'] = $sosArea;
            if (empty($datos['descripcion'])) $datos['descripcion'] = mb_substr($mensaje, 0, 600);
        }
        $reProb = '/problema|reportar|cu[eé]ntame/iu';
        if (empty($datos['descripcion']) && !$esPregunta && !$soloAdjunto && !$pideReportarExplicito && mb_strlen($mensaje) >= 10) {
            if (preg_match($reProb, $ultimoBot)) {
                $datos['descripcion'] = mb_substr($mensaje, 0, 600);
            }
        }
        if (!empty($datos['descripcion']) && empty($datos['categoria'])) $datos['categoria'] = 'otro';
        if (!empty($datos['emergencia'])) $datos['prioridad'] = 'urgente';
        elseif (($datos['prioridad'] ?? '') === 'urgente') $datos['prioridad'] = 'alta';
    }

    // ---- Confirmación ----
    $preguntoConfirmar = (bool)preg_match('/confirmas/iu', $ultimoBot);
    $reSi = '/^\s*(s[ií]|sip|claro|confirmo|correcto|ok|dale|adelante|reg[ií]stralo)[\s.,!]*(confirmo|reg[ií]stralo)?[\s.,!]*$/iu';
    $reNo = '/^\s*(no|cambiar|corregir|espera|ahora no)\b/iu';
    $dijoSi = $preguntoConfirmar && (bool)preg_match($reSi, $mensaje);
    $niega  = $preguntoConfirmar && (bool)preg_match($reNo, $mensaje);

    $enFlujo    = (!empty($datos['modo_reporte']) or !empty($datos['descripcion']));
    $faltan     = $enFlujo ? faltantes($datos) : [];
    $confirmado = $enFlujo && $dijoSi && $faltan === [];
    $siguiente  = $enFlujo ? pregunta_siguiente($datos, $faltan) : '';

    if ($infoFolioTxt !== '' && (($respuesta === '') or !str_contains($respuesta, $folio))) {
        $respuesta = $infoFolioTxt;
    }

    // ---- Qué responder ----
    if ($soloAdjunto) {
        $respuesta = $siguiente;
    } elseif ($niega) {
        $respuesta = 'De acuerdo, ¿qué quieres corregir: el problema, tu nombre, tu teléfono, la foto o la ubicación? (O escribe "cancelar").';
    } elseif ($confirmado) {
        /* se registra abajo */
    } elseif (!$enFlujo) {
        if ($respuesta === '') $respuesta = SALUDO;
    } elseif (($esPregunta or ($infoFolioTxt !== '')) && $respuesta !== '') {
        $respuesta .= "\n\n" . $siguiente;
    } else {
        $respuesta = $siguiente;
    }

    $out = ['session_id' => $sid];

    // ---- Registrar: todo completo y confirmado ----
    $registrar = $confirmado;
    if ($registrar) {
        $rep = registrar_reporte($pdo, $datos);
        $respuesta = "✅ Tu reporte quedó registrado con el folio {$rep['folio']}. Estatus: reporte recibido. "
            . "Guarda tu folio para darle seguimiento aquí o por WhatsApp al {$datos['telefono']}.";
        $out += ['folio' => $rep['folio'], 'estatus' => 'reporte recibido'];
        foreach (['modo_reporte','titulo','descripcion','categoria','prioridad','referencia_ubicacion','latitud','longitud','foto_url','emergencia'] as $k) {
            unset($datos[$k]);
        }
        $enFlujo = false;
        $faltan  = [];
    }

    if ($sosArea) {
        $out['emergencia'] = $sosArea;
        $respuesta = "⚠️ Esto parece una emergencia ($sosArea). Llama al 911 ahora mismo.\n" . $respuesta;
    }

    guardar_msg($pdo, $convId, 'assistant', $respuesta);
    $pdo->prepare('UPDATE conversaciones SET datos = ? WHERE id = ?')
        ->execute([json_encode($datos, JSON_UNESCAPED_UNICODE), $convId]);

    $out['accion'] = ((!$enFlujo) or $registrar or $niega) ? null : accion_de($faltan);
    if ($textoTranscrito !== '') {
        $out['transcripcion'] = $textoTranscrito;
    }
    if ($responderConVoz && $respuesta !== '') {
        $ttsData = generar_tts_local($respuesta);
        if ($ttsData) {
            $out['audio_url']     = $ttsData['url_relativa'];
            $out['audio_archivo'] = $ttsData['archivo'];
        }
    }
    json_out($out + ['respuesta' => $respuesta, 'faltan' => $registrar ? [] : $faltan]);
} catch (Throwable $e) {
    error_log('chatbot.php: ' . $e->getMessage());
    json_out(['error' => 'Error interno. Intenta de nuevo en unos segundos.'], 500);
}