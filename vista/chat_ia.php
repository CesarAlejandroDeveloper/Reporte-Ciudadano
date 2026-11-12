<!-- Burbuja flotante de Asistente IA (Respondin) con Mapa y Geocodificación Automática -->
<style>
  #iaFabBtn.ia-fab {
    position: fixed !important;
    right: 20px !important;
    bottom: 20px !important;
    z-index: 99998 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 10px !important;
    background: var(--primary, #691c32) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 999px !important;
    padding: 13px 18px !important;
    font-family: "Segoe UI", system-ui, sans-serif !important;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.28) !important;
    transition: transform 0.18s ease, box-shadow 0.18s ease !important;
  }
  #iaFabBtn.ia-fab:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.34) !important;
  }
  .ia-fab-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.28);
    flex-shrink: 0;
  }

  #iaVentanaChat.ia-ventana {
    position: fixed !important;
    right: 20px !important;
    bottom: 82px !important;
    width: 380px !important;
    max-width: calc(100vw - 24px) !important;
    height: 530px !important;
    max-height: calc(100vh - 105px) !important;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.32) !important;
    display: none !important;
    flex-direction: column !important;
    overflow: hidden !important;
    z-index: 99999 !important;
    font-family: "Segoe UI", system-ui, sans-serif !important;
  }
  #iaVentanaChat.ia-ventana.abierta {
    display: flex !important;
  }

  .ia-header {
    background: var(--primary, #691c32);
    color: #ffffff;
    padding: 13px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-shrink: 0;
  }
  .ia-header-info h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #ffffff;
  }
  .ia-header-info span {
    font-size: 0.78rem;
    opacity: 0.9;
    color: #ffffff;
  }
  .ia-header-acciones {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .ia-btn-head {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
  }
  .ia-btn-head:hover {
    background: rgba(255, 255, 255, 0.3);
  }

  .ia-terminos {
    padding: 14px 16px;
    background: #fdf2f4;
    border-bottom: 1px solid #f3d1d8;
    font-size: 0.84rem;
    color: #374151;
    flex-shrink: 0;
  }
  .ia-terminos label {
    display: flex;
    gap: 9px;
    align-items: flex-start;
    cursor: pointer;
    line-height: 1.35;
    margin: 0;
  }
  .ia-terminos input {
    margin-top: 3px;
    accent-color: var(--primary, #691c32);
  }

  .ia-mensajes {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f8fafc;
  }
  .ia-msg {
    max-width: 84%;
    padding: 10px 13px;
    border-radius: 14px;
    font-size: 0.9rem;
    line-height: 1.4;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
  }
  .ia-msg.bot {
    align-self: flex-start;
    background: #ffffff;
    color: #1f2937;
    border: 1px solid #e5e7eb;
    border-bottom-left-radius: 4px;
  }
  .ia-msg.user {
    align-self: flex-end;
    background: var(--primary, #691c32);
    color: #ffffff;
    border-bottom-right-radius: 4px;
  }
  .ia-msg.sos {
    border-left: 4px solid #dc2626;
    background: #fef2f2;
    color: #991b1b;
  }
  .ia-nota {
    align-self: center;
    font-size: 0.78rem;
    color: #6b7280;
    background: #eef2f6;
    padding: 4px 10px;
    border-radius: 999px;
    text-align: center;
  }

  /* Panel de Mini-Mapa dentro del Chat */
  .ia-mapa-overlay {
    display: none;
    flex-direction: column;
    background: #ffffff;
    border-top: 2px solid var(--primary, #691c32);
    border-bottom: 1px solid #e5e7eb;
    padding: 10px 12px;
    gap: 8px;
    flex-shrink: 0;
  }
  .ia-mapa-overlay.visible {
    display: flex;
  }
  .ia-mapa-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--primary, #691c32);
  }
  #iaMiniMapa {
    width: 100%;
    height: 175px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    background: #eef2f6;
    overflow: hidden;
  }
  .ia-mapa-estado {
    font-size: 0.76rem;
    color: #4b5563;
  }
  .ia-mapa-botones {
    display: flex;
    gap: 6px;
  }
  .ia-btn-mapa-ok {
    flex: 1;
    background: var(--primary, #691c32);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
  }
  .ia-btn-mapa-cancel {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
  }

  .ia-sugerencias {
    display: flex;
    gap: 6px;
    padding: 8px 12px;
    overflow-x: auto;
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
    flex-shrink: 0;
  }
  .ia-chip {
    white-space: nowrap;
    border: 1px solid #d1d5db;
    background: #f9fafb;
    color: #374151;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
  }
  .ia-chip:hover:not(:disabled) {
    border-color: var(--primary, #691c32);
    color: var(--primary, #691c32);
  }
  .ia-chip:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .ia-form {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px;
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
    flex-shrink: 0;
  }
  .ia-btn-extra {
    border: 1px solid #d1d5db;
    background: #f9fafb;
    color: #374151;
    border-radius: 8px;
    padding: 8px 9px;
    font-size: 0.9rem;
    cursor: pointer;
  }
  .ia-btn-extra.activo {
    border-color: var(--primary, #691c32);
    background: #fdf2f4;
    color: var(--primary, #691c32);
  }
  .ia-input {
    flex: 1;
    min-width: 0;
    padding: 9px 11px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #111827;
    background: #ffffff;
  }
  .ia-btn-enviar {
    background: var(--primary, #691c32);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 9px 13px;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
  }
  .ia-btn-enviar:disabled,
  .ia-btn-extra:disabled,
  .ia-input:disabled {
    opacity: 0.55;
    cursor: not-allowed;
  }

  @media (max-width: 480px) {
    #iaFabBtn.ia-fab {
      right: 12px !important;
      bottom: 12px !important;
      padding: 11px 14px !important;
      font-size: 0.88rem !important;
    }
    #iaVentanaChat.ia-ventana {
      right: 10px !important;
      left: 10px !important;
      bottom: 68px !important;
      width: auto !important;
      height: 76vh !important;
    }
  }
</style>

<button type="button" class="ia-fab" id="iaFabBtn" aria-label="Abrir asistente virtual">
  <span class="ia-fab-dot"></span>
  <span>💬 ¿Dudas? Asistente IA</span>
</button>

<div class="ia-ventana" id="iaVentanaChat" role="dialog" aria-label="Chat con Respondin">
  <div class="ia-header">
    <div class="ia-header-info">
      <h3>Respondin · Asistente IA</h3>
      <span>Dudas sobre reportes, trámites y folios</span>
    </div>
    <div class="ia-header-acciones">
      <button type="button" class="ia-btn-head" id="iaBtnReiniciar" title="Nueva conversación">↻</button>
      <button type="button" class="ia-btn-head" id="iaBtnCerrar" title="Cerrar chat">✕</button>
    </div>
  </div>

  <div class="ia-terminos" id="iaCajaTerminos">
    <label>
      <input type="checkbox" id="iaCheckTerminos" />
      <span>Esta conversación se guarda con fines de seguridad y seguimiento. Acepto los términos y condiciones del servicio.</span>
    </label>
  </div>

  <div class="ia-mensajes" id="iaLogMensajes" aria-live="polite"></div>

  <!-- Mini-Mapa Interactivo -->
  <div class="ia-mapa-overlay" id="iaMapaPanel">
    <div class="ia-mapa-top">
      <span>📍 Toca el mapa o mueve el pin</span>
      <span id="iaCoordsPreview">19.1738, -96.1348</span>
    </div>
    <div id="iaMiniMapa"></div>
    <div class="ia-mapa-estado" id="iaMapaEstado">Selecciona el punto exacto en Veracruz.</div>
    <div class="ia-mapa-botones">
      <button type="button" class="ia-btn-mapa-ok" id="iaBtnConfirmarMapa">Usar esta ubicación</button>
      <button type="button" class="ia-btn-mapa-cancel" id="iaBtnCerrarMapa">Cancelar</button>
    </div>
  </div>

  <div class="ia-sugerencias" id="iaSugerencias">
    <button type="button" class="ia-chip" data-pregunta="¿Cómo levanto un reporte ciudadano?" disabled>¿Cómo reportar?</button>
    <button type="button" class="ia-chip" data-pregunta="Quiero consultar el estatus de mi folio" disabled>Consultar folio</button>
    <button type="button" class="ia-chip" data-pregunta="¿Qué problemas puedo reportar aquí?" disabled>¿Qué se reporta?</button>
  </div>

  <form class="ia-form" id="iaFormChat" autocomplete="off">
    <button type="button" class="ia-btn-extra" id="iaBtnGps" title="Elegir ubicación en el mapa" disabled>📍</button>
    <button type="button" class="ia-btn-extra" id="iaBtnFoto" title="Adjuntar foto (JPG/PNG/WEBP)" disabled>📷</button>
    <input type="file" id="iaInputFoto" accept="image/jpeg,image/png,image/webp" style="display:none;" />
    <input type="text" class="ia-input" id="iaInputTexto" placeholder="Escribe tu duda o dirección..." maxlength="1000" disabled />
    <button type="submit" class="ia-btn-enviar" id="iaBtnEnviar" disabled>Enviar</button>
  </form>
</div>

<script>
(function() {
  const RUTAS_API = ['../../api/ia/chatbot.php', '../api/ia/chatbot.php'];
  let apiActiva = sessionStorage.getItem('respondin_api_url') || RUTAS_API[0];

  const fab = document.getElementById('iaFabBtn');
  const ventana = document.getElementById('iaVentanaChat');
  const btnCerrar = document.getElementById('iaBtnCerrar');
  const btnReiniciar = document.getElementById('iaBtnReiniciar');
  const cajaTerminos = document.getElementById('iaCajaTerminos');
  const checkTerminos = document.getElementById('iaCheckTerminos');
  const log = document.getElementById('iaLogMensajes');
  const form = document.getElementById('iaFormChat');
  const inputTexto = document.getElementById('iaInputTexto');
  const btnEnviar = document.getElementById('iaBtnEnviar');
  const btnGps = document.getElementById('iaBtnGps');
  const btnFoto = document.getElementById('iaBtnFoto');
  const inputFoto = document.getElementById('iaInputFoto');
  const chips = document.querySelectorAll('#iaSugerencias .ia-chip');

  const mapaPanel = document.getElementById('iaMapaPanel');
  const coordsPreview = document.getElementById('iaCoordsPreview');
  const mapaEstado = document.getElementById('iaMapaEstado');
  const btnConfirmarMapa = document.getElementById('iaBtnConfirmarMapa');
  const btnCerrarMapa = document.getElementById('iaBtnCerrarMapa');

  if (!fab || !ventana) return;

  let sessionId = sessionStorage.getItem('respondin_sid') || '';
  let esperandoUbicacion = sessionStorage.getItem('respondin_wait_loc') === '1';
  let historialVisual = [];
  try {
    historialVisual = JSON.parse(sessionStorage.getItem('respondin_historial') || '[]');
  } catch (_) {
    historialVisual = [];
  }

  let coords = null;
  let tempLat = 19.1738;
  let tempLng = -96.1348;
  let miniMapa = null;
  let miniPin = null;
  let foto = null;
  let ocupado = false;

  function setEsperandoUbicacion(valor) {
    esperandoUbicacion = !!valor;
    sessionStorage.setItem('respondin_wait_loc', esperandoUbicacion ? '1' : '0');
  }

  function estaEnElMar(lat, lng) {
    if (lat < 19.0000 || lat > 19.3000 || lng < -96.3500 || lng > -95.9500) return true;
    const costa = [
      [19.3000, -96.2100], [19.2400, -96.1820], [19.2250, -96.1620],
      [19.2120, -96.1480], [19.2080, -96.1255], [19.1980, -96.1285],
      [19.1860, -96.1185], [19.1700, -96.1115], [19.1450, -96.1005],
      [19.1250, -96.0955], [19.1020, -96.0975], [19.0920, -96.0840],
      [19.0750, -96.0390], [19.0550, -95.9790], [19.0000, -95.9500]
    ];
    for (let i = 0; i < costa.length - 1; i++) {
      const [lat1, lng1] = costa[i];
      const [lat2, lng2] = costa[i + 1];
      if (lat <= lat1 && lat >= lat2) {
        const t = (lat1 - lat) / (lat1 - lat2);
        return lng > (lng1 + t * (lng2 - lng1));
      }
    }
    return false;
  }

  // Convierte coordenadas escritas o una dirección escrita en lat/lng para que chatbot.php no se cicle
  async function resolverUbicacionDesdeTexto(texto) {
    const matchCoords = texto.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
    if (matchCoords) {
      return {
        latitude: parseFloat(matchCoords[1]),
        longitude: parseFloat(matchCoords[2])
      };
    }

    try {
      const ctrl = new AbortController();
      const timer = setTimeout(() => ctrl.abort(), 3500);
      const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(texto + ', Veracruz, Mexico')}`;
      const res = await fetch(url, { signal: ctrl.signal });
      clearTimeout(timer);
      const datos = await res.json();
      if (Array.isArray(datos) && datos.length > 0) {
        const lat = parseFloat(datos[0].lat);
        const lng = parseFloat(datos[0].lon);
        if (!estaEnElMar(lat, lng)) {
          return { latitude: lat, longitude: lng };
        }
      }
    } catch (_) {
      // Si no hay salida a internet, usa el centro de Veracruz como respaldo
    }

    return { latitude: 19.1738, longitude: -96.1348 };
  }

  function cargarLibreriaMapLibre() {
    return new Promise((resolve) => {
      if (typeof maplibregl !== 'undefined') {
        resolve(true);
        return;
      }
      if (!document.getElementById('iaMapLibreCss')) {
        const link = document.createElement('link');
        link.id = 'iaMapLibreCss';
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css';
        document.head.appendChild(link);
      }
      const script = document.createElement('script');
      script.src = 'https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js';
      script.onload = () => resolve(true);
      script.onerror = () => {
        const fallback = document.createElement('script');
        fallback.src = 'https://cdn.jsdelivr.net/npm/maplibre-gl@3.6.2/dist/maplibre-gl.js';
        fallback.onload = () => resolve(true);
        fallback.onerror = () => resolve(false);
        document.head.appendChild(fallback);
      };
      document.head.appendChild(script);
    });
  }

  function actualizarPinTemporal(lat, lng, centrar = false) {
    if (estaEnElMar(lat, lng)) {
      mapaEstado.style.color = '#dc2626';
      mapaEstado.textContent = '⚠️ Ese punto cae en el mar. Toca en tierra firme.';
      return false;
    }
    tempLat = lat;
    tempLng = lng;
    coordsPreview.textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    mapaEstado.style.color = '#166534';
    mapaEstado.textContent = 'Punto listo. Presiona "Usar esta ubicación".';
    if (miniPin && miniMapa) {
      miniPin.setLngLat([lng, lat]);
      if (centrar) miniMapa.flyTo({ center: [lng, lat], zoom: 16 });
    }
    return true;
  }

  async function abrirSelectorMapa() {
    mapaPanel.classList.add('visible');
    mapaEstado.style.color = '#4b5563';
    mapaEstado.textContent = 'Cargando mapa de Veracruz...';

    const ok = await cargarLibreriaMapLibre();
    if (!ok || typeof maplibregl === 'undefined') {
      mapaEstado.style.color = '#b45309';
      mapaEstado.textContent = 'Sin conexión al mapa. Presiona "Usar esta ubicación" o escribe la calle en el chat.';
      return;
    }

    if (!miniMapa) {
      miniMapa = new maplibregl.Map({
        container: 'iaMiniMapa',
        style: {
          version: 8,
          sources: {
            'osm-tiles': {
              type: 'raster',
              tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
              tileSize: 256
            }
          },
          layers: [{ id: 'osm-tiles', type: 'raster', source: 'osm-tiles', minzoom: 10, maxzoom: 19 }]
        },
        center: [tempLng, tempLat],
        zoom: 13
      });

      miniPin = new maplibregl.Marker({ color: '#691c32', draggable: true })
        .setLngLat([tempLng, tempLat])
        .addTo(miniMapa);

      miniPin.on('dragend', () => {
        const p = miniPin.getLngLat();
        actualizarPinTemporal(p.lat, p.lng, false);
      });

      miniMapa.on('click', (e) => {
        actualizarPinTemporal(e.lngLat.lat, e.lngLat.lng, false);
      });
    }

    setTimeout(() => { if (miniMapa) miniMapa.resize(); }, 150);

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => actualizarPinTemporal(pos.coords.latitude, pos.coords.longitude, true),
        () => {
          mapaEstado.style.color = '#4b5563';
          mapaEstado.textContent = 'Toca el mapa o arrastra el marcador rojo al lugar del problema.';
        },
        { enableHighAccuracy: true, timeout: 6000 }
      );
    } else {
      mapaEstado.textContent = 'Toca el mapa o arrastra el marcador rojo al lugar del problema.';
    }
  }

  function guardarHistorialVisual() {
    try {
      sessionStorage.setItem('respondin_historial', JSON.stringify(historialVisual.slice(-40)));
    } catch (_) {}
  }

  function agregarBurbuja(rol, texto, extra = '', persistir = true) {
    const div = document.createElement('div');
    div.className = `ia-msg ${rol === 'user' ? 'user' : 'bot'} ${extra}`.trim();
    div.textContent = texto;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;

    if (persistir) {
      historialVisual.push({ tipo: 'msg', rol, texto, extra });
      guardarHistorialVisual();
    }
    return div;
  }

  function agregarNota(texto, persistir = true) {
    const div = document.createElement('div');
    div.className = 'ia-nota';
    div.textContent = texto;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;

    if (persistir) {
      historialVisual.push({ tipo: 'nota', texto });
      guardarHistorialVisual();
    }
  }

  function habilitarControles(activo) {
    inputTexto.disabled = !activo;
    btnEnviar.disabled = !activo;
    btnGps.disabled = !activo;
    btnFoto.disabled = !activo;
    chips.forEach(c => c.disabled = !activo);
  }

  if (sessionId) {
    checkTerminos.checked = true;
    cajaTerminos.style.display = 'none';
    habilitarControles(true);
    historialVisual.forEach(item => {
      if (item.tipo === 'nota') {
        agregarNota(item.texto, false);
      } else {
        agregarBurbuja(item.rol, item.texto, item.extra || '', false);
      }
    });
  }

  async function enviarPeticionIA(formData) {
    let resp = await fetch(apiActiva, { method: 'POST', body: formData });
    if (resp.status === 404) {
      const alternativa = RUTAS_API.find(r => r !== apiActiva);
      if (alternativa) {
        resp = await fetch(alternativa, { method: 'POST', body: formData });
        if (resp.status !== 404) {
          apiActiva = alternativa;
          sessionStorage.setItem('respondin_api_url', apiActiva);
        }
      }
    }
    return resp.json();
  }

  async function llamarIA(mensaje) {
    ocupado = true;
    habilitarControles(false);
    const escribiendo = agregarBurbuja('bot', 'Escribiendo...', '', false);

    // Si el chat pidió ubicación y el usuario escribió dirección o coordenadas en texto, las resolvemos
    if (!coords && mensaje) {
      const tieneCoordsEscritas = /(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/.test(mensaje);
      if (tieneCoordsEscritas || esperandoUbicacion) {
        coords = await resolverUbicacionDesdeTexto(mensaje);
      }
    }

    const fd = new FormData();
    fd.append('mensaje', mensaje);
    fd.append('session_id', sessionId);
    fd.append('acepta_terminos', checkTerminos.checked ? 'true' : 'false');
    if (coords) {
      fd.append('latitud', coords.latitude);
      fd.append('longitud', coords.longitude);
    }
    if (foto) {
      fd.append('foto', foto);
    }

    try {
      const data = await enviarPeticionIA(fd);
      escribiendo.remove();

      if (data.requiere_terminos) {
        sessionId = '';
        sessionStorage.removeItem('respondin_sid');
        setEsperandoUbicacion(false);
        checkTerminos.checked = false;
        checkTerminos.disabled = false;
        cajaTerminos.style.display = 'block';
        agregarNota('Acepta los términos para continuar.');
        ocupado = false;
        return;
      }

      if (data.session_id) {
        sessionId = data.session_id;
        sessionStorage.setItem('respondin_sid', sessionId);
      }

      const textoRespuesta = (data.respuesta || data.error || 'Sin respuesta')
        + (data.respuesta && data.error ? `\n[Detalle: ${data.error}]` : '');

      // Detectar si el asistente está pidiendo la ubicación
      const lowerResp = textoRespuesta.toLowerCase();
      if (
        lowerResp.includes('dónde está el problema') ||
        lowerResp.includes('elegir la ubicación') ||
        lowerResp.includes('ubicación actual')
      ) {
        setEsperandoUbicacion(true);
      } else {
        setEsperandoUbicacion(false);
      }

      agregarBurbuja('bot', textoRespuesta, data.emergencia ? 'sos' : '');

      if (data.folio) {
        setEsperandoUbicacion(false);
        agregarNota(`Folio generado: ${data.folio}`);
        if (typeof cargarReportesBD === 'function') {
          cargarReportesBD();
        }
      }

      coords = null;
      foto = null;
      btnGps.classList.remove('activo');
      btnFoto.classList.remove('activo');
    } catch (err) {
      escribiendo.remove();
      agregarBurbuja('bot', 'No pude conectar con el servicio de IA. Verifica que Apache y Ollama estén activos.');
    }

    ocupado = false;
    habilitarControles(true);
    inputTexto.focus();
  }

  fab.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    ventana.classList.toggle('abierta');
    if (ventana.classList.contains('abierta') && checkTerminos.checked) {
      inputTexto.focus();
    }
  });

  btnCerrar.addEventListener('click', (e) => {
    e.preventDefault();
    ventana.classList.remove('abierta');
  });

  btnReiniciar.addEventListener('click', () => {
    sessionId = '';
    historialVisual = [];
    coords = null;
    foto = null;
    setEsperandoUbicacion(false);
    sessionStorage.removeItem('respondin_sid');
    sessionStorage.removeItem('respondin_historial');
    log.innerHTML = '';
    mapaPanel.classList.remove('visible');
    checkTerminos.checked = false;
    checkTerminos.disabled = false;
    cajaTerminos.style.display = 'block';
    btnGps.classList.remove('activo');
    btnFoto.classList.remove('activo');
    habilitarControles(false);
  });

  checkTerminos.addEventListener('change', async (e) => {
    if (!e.target.checked || sessionId) return;
    e.target.disabled = true;
    cajaTerminos.style.display = 'none';
    await llamarIA('');
  });

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      if (ocupado || !checkTerminos.checked) return;
      const pregunta = chip.dataset.pregunta;
      agregarBurbuja('user', pregunta);
      llamarIA(pregunta);
    });
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const texto = inputTexto.value.trim();
    if ((!texto && !foto && !coords) || ocupado || !checkTerminos.checked) return;

    const etiquetaAuto = coords
      ? `[Ubicación: ${coords.latitude.toFixed(5)}, ${coords.longitude.toFixed(5)}]`
      : '[Foto adjunta]';

    agregarBurbuja('user', texto || etiquetaAuto);
    inputTexto.value = '';
    llamarIA(texto);
  });

  btnGps.addEventListener('click', () => {
    if (mapaPanel.classList.contains('visible')) {
      mapaPanel.classList.remove('visible');
    } else {
      abrirSelectorMapa();
    }
  });

  btnConfirmarMapa.addEventListener('click', () => {
    if (estaEnElMar(tempLat, tempLng)) {
      mapaEstado.style.color = '#dc2626';
      mapaEstado.textContent = '⚠️ Coloca el pin en tierra firme antes de confirmar.';
      return;
    }
    coords = { latitude: tempLat, longitude: tempLng };
    btnGps.classList.add('activo');
    mapaPanel.classList.remove('visible');
    agregarNota(`Ubicación fijada: ${tempLat.toFixed(5)}, ${tempLng.toFixed(5)}`);
  });

  btnCerrarMapa.addEventListener('click', () => {
    mapaPanel.classList.remove('visible');
  });

  btnFoto.addEventListener('click', () => inputFoto.click());

  inputFoto.addEventListener('change', (e) => {
    const f = e.target.files[0];
    if (!f) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(f.type)) {
      alert('Solo se permiten imágenes JPG, PNG o WEBP.');
      inputFoto.value = '';
      return;
    }
    if (f.size > 5 * 1024 * 1024) {
      alert('La imagen para el chat no debe superar los 5 MB.');
      inputFoto.value = '';
      return;
    }
    foto = f;
    btnFoto.classList.add('activo');
    agregarNota(`Foto lista: ${f.name}`);
  });
})();
</script>