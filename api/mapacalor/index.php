<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sesionValida = !empty($_SESSION['usuario'])
    || !empty($_SESSION['usuario_id'])
    || !empty($_SESSION['user_id'])
    || !empty($_SESSION['id_usuario'])
    || !empty($_SESSION['admin'])
    || !empty($_SESSION['rol'])
    || !empty($_SESSION['user'])
    || !empty($_SESSION['logueado']);

if (!$sesionValida) {
    http_response_code(403);
    header('Location: ../../vista/Home.php?error=acceso_denegado');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mapa de calor · Reportes ciudadanos</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<!-- Misma librería que api/mapa: copia local si existe, si no, internet -->
<script src="../mapa/protomaps-leaflet.js"></script>
<script>window.protomapsL || document.write('<script src="https://unpkg.com/protomaps-leaflet@5.0.0/dist/protomaps-leaflet.js"><\/script>')</script>
<style>
  :root { --ink:#1d2430; --muted:#5f6b7a; --bg:#f3f1ee; --line:#d9d4cc; --brand:#1d4f91; }
  * { box-sizing: border-box; }
  html, body { height: 100%; margin: 0; }
  body { font: 15px/1.4 "Segoe UI", system-ui, sans-serif; color: var(--ink); display: flex; }
  #mapa { flex: 1; min-width: 0; height: 100%; }
  aside { width: 360px; max-width: 100%; height: 100%; overflow-y: auto; background: var(--bg); border-left: 1px solid var(--line); padding: 16px; }
  h1 { font-size: 1.2rem; margin: 0 0 2px; }
  h2 { font-size: 1rem; margin: 18px 0 8px; }
  p { margin: 0; }
  .muted { color: var(--muted); font-size: .88rem; }
  .niveles { display: grid; gap: 6px; margin-top: 12px; }
  .nivel { display: flex; align-items: center; gap: 10px; }
  .chip { width: 14px; height: 14px; border-radius: 4px; flex: none; }
  .nivel b { min-width: 70px; }
  .acciones { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 14px; }
  button { font: inherit; cursor: pointer; padding: 7px 11px; border: 1px solid var(--line); border-radius: 8px; background: #fff; color: var(--ink); }
  button.on { background: var(--brand); color: #fff; border-color: var(--brand); }
  input[type=number] { width: 64px; font: inherit; padding: 6px; border: 1px solid var(--line); border-radius: 8px; }
  .aviso { margin-top: 12px; padding: 10px 12px; border-radius: 8px; background: #fff4d6; border: 1px solid #ecd48a; font-size: .88rem; }
  .error { background: #fde4e1; border-color: #e9a59e; }
  .rep { background: #fff; border: 1px solid var(--line); border-left-width: 6px; border-radius: 8px; padding: 9px 11px; margin-bottom: 8px; }
  .rep b { display: block; }
  .rep small { color: var(--muted); display: block; }
  .rep p { margin-top: 4px; font-size: .9rem; }
  button:focus-visible, input:focus-visible { outline: 3px solid var(--brand); outline-offset: 2px; }
  @media (max-width: 760px) {
    body { flex-direction: column; }
    #mapa { height: 55%; flex: none; }
    aside { width: 100%; height: 45%; border-left: 0; border-top: 1px solid var(--line); }
  }
</style>
</head>
<body>
<div id="mapa"></div>
<aside aria-live="polite">
  <h1>Mapa de calor de reportes</h1>
  <p class="muted" id="resumen">Cargando reportes…</p>
  <div class="niveles" id="niveles"></div>
  <div class="acciones">
    <label class="muted" for="radio">Radio del clic (m)</label>
    <input type="number" id="radio" min="1" max="500" value="5">
    <button id="btnDemo" type="button">Ver datos de prueba</button>
  </div>
  <div id="avisos"></div>
  <h2 id="tituloLista">Haz clic en el mapa</h2>
  <div id="lista"><p class="muted">Se mostrarán los reportes que estén dentro del radio elegido alrededor del punto donde hagas clic.</p></div>
</aside>
<script>
const CFG = {
  ZONA_M: 300,                 // tamaño de cada zona (cuadro) en metros
  RADIO_M: 5,                  // radio por defecto del clic
  SEGUNDOS: 30,                // cada cuánto se recargan los reportes (0 = nunca)
  CENTRO: [19.1738, -96.1348],
  LIMITES: [[17.1, -98.7], [22.5, -93.6]],
  ARCHIVO_MAPA: '../mapa/veracruz.pmtiles',
  API: 'datos.php',
  NIVELES: [                   // de mayor a menor
    { min: 50, nombre: 'Grave',    color: '#d62839' },
    { min: 20, nombre: 'Notable',  color: '#f57c00' },
    { min: 10, nombre: 'Moderado', color: '#f4b400' }
  ],
  SIN_NIVEL: { nombre: 'Sin nivel', color: '#64748b' }
};
const $ = id => document.getElementById(id);
const M_LAT = 111320, M_LNG = M_LAT * Math.cos(CFG.CENTRO[0] * Math.PI / 180);
const nivelDe = n => CFG.NIVELES.find(x => n >= x.min) || null;
const celda = (lat, lng) => [Math.floor(lat * M_LAT / CFG.ZONA_M), Math.floor(lng * M_LNG / CFG.ZONA_M)];
const clave = (cy, cx) => cy + ':' + cx;

function metros(lat1, lng1, lat2, lng2) {
  const r = Math.PI / 180, R = 6371008.8;
  const a = Math.sin((lat2 - lat1) * r / 2) ** 2 +
            Math.cos(lat1 * r) * Math.cos(lat2 * r) * Math.sin((lng2 - lng1) * r / 2) ** 2;
  return 2 * R * Math.asin(Math.sqrt(a));
}

function el(tag, cls, txt) {
  const e = document.createElement(tag);
  if (cls) e.className = cls;
  if (txt !== undefined) e.textContent = txt;
  return e;
}

const mapa = L.map('mapa', { minZoom: 7, maxZoom: 19, maxBounds: CFG.LIMITES, maxBoundsViscosity: 1, preferCanvas: true })
  .setView(CFG.CENTRO, 13);

if (window.protomapsL) {
  protomapsL.leafletLayer({
    url: CFG.ARCHIVO_MAPA,
    flavor: 'light',
    maxDataZoom: 14,
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap | Protomaps'
  }).addTo(mapa);
} else {
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(mapa);
}

const capaZonas = L.layerGroup().addTo(mapa);
const capaPuntos = L.layerGroup().addTo(mapa);
const capaClic = L.layerGroup().addTo(mapa);

let reportes = [], zonas = new Map(), demo = false, primeraVez = true;

function construir(lista) {
  reportes = lista;
  zonas = new Map();
  for (const r of reportes) {
    const [cy, cx] = celda(r.lat, r.lng);
    r._k = clave(cy, cx);
    const z = zonas.get(r._k) || { cy, cx, n: 0 };
    z.n++;
    zonas.set(r._k, z);
  }
  for (const z of zonas.values()) z.nivel = nivelDe(z.n);
  capaZonas.clearLayers();
  capaPuntos.clearLayers();
  for (const z of zonas.values()) {
    if (!z.nivel) continue;
    L.rectangle(
      [[z.cy * CFG.ZONA_M / M_LAT, z.cx * CFG.ZONA_M / M_LNG],
       [(z.cy + 1) * CFG.ZONA_M / M_LAT, (z.cx + 1) * CFG.ZONA_M / M_LNG]],
      { color: z.nivel.color, weight: 1, fillColor: z.nivel.color, fillOpacity: 0.38, interactive: false }
    ).addTo(capaZonas);
  }
  for (const r of reportes) {
    const c = (zonas.get(r._k).nivel || CFG.SIN_NIVEL).color;
    L.circleMarker([r.lat, r.lng], { radius: 4, color: '#fff', weight: 1, fillColor: c, fillOpacity: 1, interactive: false }).addTo(capaPuntos);
  }
  pintarNiveles();
}

function pintarNiveles() {
  const cont = $('niveles');
  cont.replaceChildren();
  for (const n of CFG.NIVELES) {
    const zs = [...zonas.values()].filter(z => z.nivel === n);
    const f = el('div', 'nivel');
    const chip = el('span', 'chip');
    chip.style.background = n.color;
    f.append(chip, el('b', '', n.nombre), el('span', 'muted', n.min + '+ reportes por zona · ' + zs.length + ' zona(s)'));
    cont.append(f);
  }
  const f = el('div', 'nivel'), chip = el('span', 'chip');
  chip.style.background = CFG.SIN_NIVEL.color;
  f.append(chip, el('b', '', 'Sin nivel'), el('span', 'muted', 'menos de ' + CFG.NIVELES.at(-1).min + ' (solo se muestra el punto)'));
  cont.append(f);
}

function avisar(txt, error) {
  $('avisos').replaceChildren();
  if (txt) $('avisos').append(el('div', 'aviso' + (error ? ' error' : ''), txt));
}

async function cargar() {
  if (demo) return;
  try {
    const r = await fetch(CFG.API + '?t=' + Date.now(), { cache: 'no-store' });
    const d = await r.json();
    if (!r.ok || d.error) throw new Error(d.error || 'HTTP ' + r.status);
    construir(d.reportes);
    $('resumen').textContent = d.con_ubicacion + ' de ' + d.total + ' reportes tienen ubicación (latitud y longitud).';
    avisar(d.con_ubicacion === 0
      ? 'Todavía no hay reportes con ubicación. Usa "Ver datos de prueba" para ver cómo se verá el mapa.'
      : (d.con_ubicacion < d.total ? 'Los reportes sin latitud y longitud no pueden dibujarse en el mapa.' : ''));
    if (primeraVez && reportes.length) {
      mapa.fitBounds(L.latLngBounds(reportes.map(r => [r.lat, r.lng])), { maxZoom: 16, padding: [40, 40] });
    }
    primeraVez = false;
  } catch (e) {
    avisar('No se pudieron cargar los reportes: ' + e.message, true);
    $('resumen').textContent = '';
  }
}

function generarDemo() {
  let s = 7;
  const rnd = () => (s = (s * 16807) % 2147483647) / 2147483647;
  const cats = ['bache', 'alumbrado', 'fuga_agua', 'basura', 'seguridad', 'accidente'];
  const pris = ['baja', 'media', 'alta', 'urgente'];
  const out = [];
  let id = 9000;
  const poner = (lat, lng, extra) => {
    const c = cats[Math.floor(rnd() * cats.length)];
    out.push({
      id: ++id, folio: 'DEMO-' + id, titulo: 'Reporte de prueba: ' + c, descripcion: 'Dato simulado para probar el mapa.',
      categoria: c, prioridad: pris[Math.floor(rnd() * 4)], estatus: 'reporte recibido', lat, lng, fecha: '2026-11-11 12:00:00', ...extra
    });
  };
  const grupo = (lat, lng, n, dispersion) => {
    for (let i = 0; i < n; i++)
      poner(lat + (rnd() - .5) * dispersion / M_LAT, lng + (rnd() - .5) * dispersion / M_LNG);
  };
  const base = [Math.floor(19.1738 * M_LAT / CFG.ZONA_M) * CFG.ZONA_M / M_LAT, Math.floor(-96.1348 * M_LNG / CFG.ZONA_M) * CFG.ZONA_M / M_LNG];
  const centro = (dy, dx) => [base[0] + (dy + .5) * CFG.ZONA_M / M_LAT, base[1] + (dx + .5) * CFG.ZONA_M / M_LNG];
  grupo(...centro(0, 0), 55, 200);
  grupo(...centro(0, 2), 24, 200);
  grupo(...centro(2, 1), 12, 200);
  grupo(...centro(-2, -1), 5, 200);
  grupo(...centro(1, -3), 3, 200);
  const [la, ln] = centro(0, 0);
  for (let i = 0; i < 4; i++) poner(la + (rnd() - .5) * 4 / M_LAT, ln + (rnd() - .5) * 4 / M_LNG);
  return out;
}

$('btnDemo').addEventListener('click', () => {
  demo = !demo;
  $('btnDemo').classList.toggle('on', demo);
  $('btnDemo').textContent = demo ? 'Volver a datos reales' : 'Ver datos de prueba';
  capaClic.clearLayers();
  if (demo) {
    construir(generarDemo());
    $('resumen').textContent = 'Modo prueba: ' + reportes.length + ' reportes simulados (no están en la base de datos).';
    avisar('');
    mapa.fitBounds(L.latLngBounds(reportes.map(r => [r.lat, r.lng])), { maxZoom: 16, padding: [40, 40] });
  } else {
    primeraVez = true;
    cargar();
  }
});

mapa.on('click', e => {
  const R = Math.max(1, Math.min(500, +$('radio').value || CFG.RADIO_M));
  const { lat, lng } = e.latlng;
  capaClic.clearLayers();
  L.circle(e.latlng, { radius: R, color: '#1d4f91', weight: 2, fillOpacity: 0.15, interactive: false }).addTo(capaClic);
  L.circleMarker(e.latlng, { radius: 3, color: '#1d4f91', fillColor: '#1d4f91', fillOpacity: 1, interactive: false }).addTo(capaClic);
  const cerca = reportes.map(r => ({ r, d: metros(lat, lng, r.lat, r.lng) })).filter(x => x.d <= R).sort((a, b) => a.d - b.d);
  const [cy, cx] = celda(lat, lng), z = zonas.get(clave(cy, cx));
  const lista = $('lista');
  lista.replaceChildren();
  $('tituloLista').textContent = cerca.length + ' reporte(s) a ' + R + ' m';
  const info = z ? z.n + ' reporte(s) en esta zona de ' + CFG.ZONA_M + ' m (' + (z.nivel ? 'nivel ' + z.nivel.nombre.toLowerCase() : 'sin nivel') + ').'
                 : 'No hay reportes en esta zona de ' + CFG.ZONA_M + ' m.';
  lista.append(el('p', 'muted', info));
  lista.lastChild.style.marginBottom = '10px';
  if (!cerca.length) {
    const m = reportes.length ? reportes.reduce((a, r) => Math.min(a, metros(lat, lng, r.lat, r.lng)), Infinity) : null;
    lista.append(el('p', 'muted', m === null ? 'No hay reportes cargados.'
      : 'Ninguno en este radio. El más cercano está a ' + Math.round(m) + ' m; prueba con un radio mayor.'));
    return;
  }
  for (const { r, d } of cerca) {
    const nivel = zonas.get(r._k).nivel || CFG.SIN_NIVEL;
    const t = el('div', 'rep');
    t.style.borderLeftColor = nivel.color;
    t.append(
      el('b', '', r.titulo || '(sin título)'),
      el('small', '', r.folio + ' · ' + nivel.nombre + ' · a ' + d.toFixed(1) + ' m'),
      el('small', '', [r.categoria, r.prioridad, r.estatus].filter(Boolean).join(' · ')),
      el('small', '', r.fecha || '')
    );
    if (r.descripcion) t.append(el('p', '', r.descripcion));
    lista.append(t);
  }
});

pintarNiveles();
cargar();
if (CFG.SEGUNDOS > 0) setInterval(cargar, CFG.SEGUNDOS * 1000);
</script>
</body>
</html>