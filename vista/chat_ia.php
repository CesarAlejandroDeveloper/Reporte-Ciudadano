<!-- Burbuja flotante de Asistente IA (Respondin) -->
<style>
  .ia-fab {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 9990;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--primary, #691c32);
    color: #ffffff;
    border: none;
    border-radius: 999px;
    padding: 13px 18px;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }
  .ia-fab:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.3);
  }
  .ia-fab-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.28);
  }

  .ia-ventana {
    position: fixed;
    right: 22px;
    bottom: 84px;
    width: 375px;
    max-width: calc(100vw - 24px);
    height: 520px;
    max-height: calc(100vh - 110px);
    background: #ffffff;
    border: 1px solid var(--border, #d1d5db);
    border-radius: 16px;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.28);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 9991;
    font-family: "Segoe UI", system-ui, sans-serif;
  }
  .ia-ventana.abierta {
    display: flex;
  }

  .ia-header {
    background: var(--primary, #691c32);
    color: #ffffff;
    padding: 13px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .ia-header-info h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
  }
  .ia-header-info span {
    font-size: 0.78rem;
    opacity: 0.9;
  }
  .ia-header-acciones {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .ia-btn-head {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 5px 9px;
    font-size: 0.8rem;
    cursor: pointer;
  }
  .ia-btn-head:hover {
    background: rgba(255, 255, 255, 0.28);
  }

  .ia-terminos {
    padding: 14px 16px;
    background: #fdf2f4;
    border-bottom: 1px solid #f3d1d8;
    font-size: 0.84rem;
    color: #374151;
  }
  .ia-terminos label {
    display: flex;
    gap: 9px;
    align-items: flex-start;
    cursor: pointer;
    line-height: 1.35;
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

  .ia-sugerencias {
    display: flex;
    gap: 6px;
    padding: 8px 12px;
    overflow-x: auto;
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
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
    .ia-fab {
      right: 14px;
      bottom: 14px;
      padding: 12px 15px;
    }
    .ia-ventana {
      right: 12px;
      left: 12px;
      bottom: 72px;
      width: auto;
    }
  }
</style>

<button type="button" class="ia-fab" id="iaFabBtn" aria-label="Abrir asistente virtual">
  <span class="ia-fab-dot"></span>
  <span>💬 ¿Dudas? Asistente IA</span>
</button>

<aside class="ia-ventana" id="iaVentanaChat" aria-label="Chat con Respondin">
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

  <div class="ia-sugerencias" id="iaSugerencias">
    <button type="button" class="ia-chip" data-pregunta="¿Cómo levanto un reporte ciudadano?" disabled>¿Cómo reportar?</button>
    <button type="button" class="ia-chip" data-pregunta="Quiero consultar el estatus de mi folio" disabled>Consultar folio</button>
    <button type="button" class="ia-chip" data-pregunta="¿Qué problemas puedo reportar aquí?" disabled>¿Qué se reporta?</button>
  </div>

  <form class="ia-form" id="iaFormChat" autocomplete="off">
    <button type="button" class="ia-btn-extra" id="iaBtnGps" title="Adjuntar ubicación GPS" disabled>📍</button>
    <button type="button" class="ia-btn-extra" id="iaBtnFoto" title="Adjuntar foto (JPG/PNG/WEBP)" disabled>📷</button>
    <input type="file" id="iaInputFoto" accept="image/jpeg,image/png,image/webp" style="display:none;" />
    <input type="text" class="ia-input" id="iaInputTexto" placeholder="Escribe tu duda aquí..." maxlength="1000" disabled />
    <button type="submit" class="ia-btn-enviar" id="iaBtnEnviar" disabled>Enviar</button>
  </form>
</aside>

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

  let sessionId = sessionStorage.getItem('respondin_sid') || '';
  let historialVisual = JSON.parse(sessionStorage.getItem('respondin_historial') || '[]');
  let coords = null;
  let foto = null;
  let ocupado = false;

  function guardarHistorialVisual() {
    sessionStorage.setItem('respondin_historial', JSON.stringify(historialVisual.slice(-40)));
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

      agregarBurbuja('bot', textoRespuesta, data.emergencia ? 'sos' : '');

      if (data.folio) {
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

  fab.addEventListener('click', () => {
    ventana.classList.toggle('abierta');
    if (ventana.classList.contains('abierta') && checkTerminos.checked) {
      inputTexto.focus();
    }
  });

  btnCerrar.addEventListener('click', () => {
    ventana.classList.remove('abierta');
  });

  btnReiniciar.addEventListener('click', () => {
    sessionId = '';
    historialVisual = [];
    coords = null;
    foto = null;
    sessionStorage.removeItem('respondin_sid');
    sessionStorage.removeItem('respondin_historial');
    log.innerHTML = '';
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

    agregarBurbuja('user', texto || (foto ? '[Foto adjunta]' : '[Ubicación GPS compartida]'));
    inputTexto.value = '';
    llamarIA(texto);
  });

  btnGps.addEventListener('click', () => {
    if (!navigator.geolocation) {
      agregarNota('Tu navegador no soporta GPS.');
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        coords = pos.coords;
        btnGps.classList.add('activo');
        agregarNota('Ubicación lista; se enviará con tu mensaje.');
      },
      () => agregarNota('No se pudo obtener la ubicación.'),
      { enableHighAccuracy: true, timeout: 10000 }
    );
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