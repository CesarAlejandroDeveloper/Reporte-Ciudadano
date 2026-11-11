<?php
/**
 * ollama.php — Puerta de entrada a la IA (Ollama)
 *
 *  GET  ollama.php?action=status   → ¿está encendido? ¿qué modelos hay?
 *  GET  ollama.php?action=warm     → enciende Ollama y precarga el modelo
 *  POST ollama.php  {"prompt":"...", "system":"...", "json":false, "model":"..."}
 *  POST ollama.php  {"messages":[{"role":"user","content":"..."}]}
 */
require __DIR__ . '/config.php';

$accion = $_GET['action'] ?? 'chat';

try {
    if ($accion === 'status') {
        $up = ollama_up();
        $modelos = [];
        if ($up) {
            $r = json_decode((string)@file_get_contents(OLLAMA_URL . '/api/tags'), true);
            foreach (($r['models'] ?? []) as $m) $modelos[] = $m['name'];
        }
        json_out(['online' => $up, 'modelo_por_defecto' => OLLAMA_MODEL, 'modelos' => $modelos]);
    }

    if ($accion === 'warm') {
        ollama_post('/api/chat', ['model' => OLLAMA_MODEL, 'messages' => [], 'stream' => false, 'keep_alive' => '30m']);
        json_out(['online' => true, 'modelo' => OLLAMA_MODEL, 'precargado' => true]);
    }

    $in = entrada();
    if (!empty($in['messages']) && is_array($in['messages'])) {
        $msgs = $in['messages'];
    } else {
        $prompt = trim((string)($in['prompt'] ?? ''));
        if ($prompt === '') json_out(['error' => 'Falta "prompt" o "messages"'], 400);
        $msgs = [];
        if (!empty($in['system'])) $msgs[] = ['role' => 'system', 'content' => (string)$in['system']];
        $msgs[] = ['role' => 'user', 'content' => $prompt];
    }
    $texto = ollama_chat($msgs, !empty($in['json']), $in['model'] ?? null);
    json_out(['respuesta' => $texto]);
} catch (Throwable $e) {
    json_out(['error' => $e->getMessage()], 503);
}
