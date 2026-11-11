<?php
/**
 * estatus.php
 *  GET  ?folio=RC-000000-0000&telefono=2291234567  → estatus (el teléfono debe coincidir)
 *  POST {"folio":"RC-...","estatus":"en analisis"}  + cabecera X-Api-Key → cambia estatus
 *       y deja el aviso de WhatsApp en la cola `notificaciones` (enviado=0)
 */
require __DIR__ . '/config.php';

try {
    $pdo = db();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $folio = strtoupper(trim((string)($_GET['folio'] ?? '')));
        $tel   = substr(preg_replace('/\D/', '', (string)($_GET['telefono'] ?? '')), -10);
        $st = $pdo->prepare('SELECT r.folio, r.titulo, r.estatus, r.fecha_creacion
                             FROM reportes r JOIN ciudadanos c ON c.id = r.ciudadano_id
                             WHERE r.folio = ? AND RIGHT(c.telefono, 10) = ?');
        $st->execute([$folio, $tel]);
        $r = $st->fetch();
        if (!$r) json_out(['error' => 'No encontrado'], 404);
        $h = $pdo->prepare('SELECT estatus, creado FROM historial_estatus
                            WHERE reporte_id = (SELECT id FROM reportes WHERE folio = ?) ORDER BY id');
        $h->execute([$folio]);
        json_out($r + ['historial' => $h->fetchAll()]);
    }

    // POST (administrativo)
    if (!hash_equals(ADMIN_TOKEN, $_SERVER['HTTP_X_API_KEY'] ?? '')) json_out(['error' => 'No autorizado'], 401);
    $in      = entrada();
    $folio   = strtoupper(trim((string)($in['folio'] ?? '')));
    $estatus = (string)($in['estatus'] ?? '');
    if (!in_array($estatus, ESTATUS, true)) json_out(['error' => 'Estatus inválido', 'validos' => ESTATUS], 400);

    $st = $pdo->prepare('SELECT r.id, c.telefono FROM reportes r LEFT JOIN ciudadanos c ON c.id = r.ciudadano_id WHERE r.folio = ?');
    $st->execute([$folio]);
    $r = $st->fetch();
    if (!$r) json_out(['error' => 'Folio no existe'], 404);

    $pdo->prepare('UPDATE reportes SET estatus = ? WHERE id = ?')->execute([$estatus, $r['id']]);
    $pdo->prepare('INSERT INTO historial_estatus (reporte_id, estatus) VALUES (?,?)')->execute([$r['id'], $estatus]);
    if ($r['telefono']) {
        $pdo->prepare('INSERT INTO notificaciones (reporte_id, telefono, estatus, mensaje) VALUES (?,?,?,?)')
            ->execute([$r['id'], $r['telefono'], $estatus, "Tu reporte $folio ahora está: $estatus."]);
    }
    json_out(['ok' => true, 'folio' => $folio, 'estatus' => $estatus]);
} catch (Throwable $e) {
    json_out(['error' => 'Error interno', 'detalle' => $e->getMessage()], 500);
}
