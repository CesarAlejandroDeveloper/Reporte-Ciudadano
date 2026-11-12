<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Hacer un Reporte - CiudadReporta</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
  <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
  <style>
    .portal-wrapper {
      max-width: 1220px;
      margin: 0 auto;
      padding: 20px;
    }
    .portal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      padding-bottom: 16px;
      margin-bottom: 24px;
      border-bottom: 2px solid var(--border, #e5e7eb);
    }
    .portal-header h1 {
      margin: 0;
      color: var(--primary, #691c32);
      font-size: 1.55rem;
    }
    .btn-volver {
      display: inline-block;
      padding: 8px 14px;
      border-radius: 6px;
      background: #f3f4f6;
      color: #1f2937;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      border: 1px solid #d1d5db;
    }
    .portal-grid {
      display: grid;
      grid-template-columns: 1fr 1.25fr;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 900px) {
      .portal-grid {
        grid-template-columns: 1fr;
      }
    }
    .panel-card {
      background: var(--panel, #ffffff);
      border: 1px solid var(--border, #e5e7eb);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
    }
    .panel-title-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 8px;
      margin-bottom: 14px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--border, #e5e7eb);
    }
    .panel-title-row h2 {
      margin: 0;
      font-size: 1.15rem;
      color: var(--primary, #691c32);
    }
    .contador-pill {
      font-size: 0.78rem;
      font-weight: 700;
      background: #fdf2f4;
      color: var(--primary, #691c32);
      padding: 4px 10px;
      border-radius: 999px;
      border: 1px solid #f3d1d8;
    }

    /* Tarjetas de reportes recientes (máximo 5 por defecto) */
    .report-card {
      border: 1px solid var(--border, #e5e7eb);
      border-left: 4px solid var(--primary, #691c32);
      border-radius: 8px;
      padding: 12px 14px;
      margin-bottom: 12px;
      background: #ffffff;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .report-card:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
    }
    .report-card-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 8px;
      margin-bottom: 6px;
    }
    .badge-st {
      display: inline-block;
      padding: 3px 9px;
      border-radius: 12px;
      font-size: 0.74rem;
      font-weight: 700;
      text-transform: uppercase;
    }
    .st-recibido { background: #e0f2fe; color: #0369a1; }
    .st-revision { background: #fef3c7; color: #92400e; }
    .st-proceso  { background: #ede9fe; color: #5b21b6; }
    .st-resuelto { background: #dcfce7; color: #166534; }

    .btn-toggle-lista {
      width: 100%;
      padding: 10px;
      margin-top: 4px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      background: #f9fafb;
      color: #374151;
      font-weight: 600;
      font-size: 0.88rem;
      cursor: pointer;
    }
    .btn-toggle-lista:hover {
      background: #f3f4f6;
      border-color: var(--primary, #691c32);
      color: var(--primary, #691c32);
    }

    /* Formulario */
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--text, #1f2937);
      margin-bottom: 6px;
    }
    .input-mobile {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border, #d1d5db);
      border-radius: 8px;
      font-size: 0.95rem;
      font-family: inherit;
      box-sizing: border-box;
    }
    .ubicacion-row {
      display: flex;
      gap: 8px;
    }
    .btn-gps {
      padding: 10px 12px;
      border: 1px solid #d1d5db;
      background: #f9fafb;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      white-space: nowrap;
    }
    .btn-gps:hover {
      border-color: var(--primary, #691c32);
      color: var(--primary, #691c32);
    }
    #mapaReporte {
      width: 100%;
      height: 240px;
      border-radius: 10px;
      border: 1px solid var(--border, #d1d5db);
      margin-top: 8px;
    }
    .helper-text {
      display: block;
      font-size: 0.8rem;
      color: var(--muted, #6b7280);
      margin-top: 4px;
    }
    .preview-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      margin-top: 10px;
    }
    .preview-grid img {
      width: 100%;
      height: 90px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid #d1d5db;
    }
    .btn-submit {
      width: 100%;
      padding: 13px;
      background: var(--primary, #691c32);
      color: #ffffff;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 6px;
    }
    .btn-submit:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    .alerta-form {
      display: none;
      padding: 12px 14px;
      border-radius: 8px;
      margin-bottom: 14px;
      font-size: 0.9rem;
      font-weight: 600;
    }
    .alerta-ok {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #86efac;
    }
    .alerta-err {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
    }
  </style>
</head>
<body>
  <main class="portal-wrapper">
    <header class="portal-header">
      <div>
        <h1>CiudadReporta · Atención Ciudadana</h1>
        <span style="color: var(--muted, #6b7280); font-size: 0.9rem;">
          Reporta incidencias urbanas en Veracruz y da seguimiento por folio
        </span>
      </div>
      <a href="Home.php" class="btn-volver">← Volver al inicio</a>
    </header>

    <div class="portal-grid">
      <!-- COLUMNA IZQUIERDA: MÁXIMO 5 REPORTES RECIENTES -->
      <section class="panel-card">
        <div class="panel-title-row">
          <h2>Reportes Recientes</h2>
          <span class="contador-pill" id="contadorVisibles">Cargando...</span>
        </div>
        <div id="listaReportes">
          <p style="color: var(--muted, #6b7280); text-align: center; padding: 20px 0;">
            Cargando reportes recientes...
          </p>
        </div>
      </section>

      <!-- COLUMNA DERECHA: FORMULARIO PARA GENERAR REPORTE -->
      <section class="panel-card">
        <div class="panel-title-row">
          <h2>Nuevo Reporte Ciudadano</h2>
        </div>

        <div id="alertaFormulario" class="alerta-form"></div>

        <form id="formReporte" enctype="multipart/form-data">
          <div class="form-group">
            <label for="tipo_incidencia">Tipo de incidencia *</label>
            <select id="tipo_incidencia" name="tipo_incidencia" class="input-mobile" required>
              <option value="">Selecciona una opción...</option>
              <option value="bache">Bache en vialidad</option>
              <option value="luminaria">Luminaria fundida / farol</option>
              <option value="fuga">Fuga de agua / drenaje</option>
              <option value="basura">Basura / tiradero clandestino</option>
              <option value="accidente">Daño por accidente vial</option>
              <option value="tramite">Consulta / trámite municipal</option>
              <option value="seguridad">Vandalismo / riesgo en vía pública</option>
              <option value="otro">Otro</option>
            </select>
          </div>

          <div class="form-group">
            <label for="ubicacion">Ubicación (escribe dirección y presiona Enter, o mueve el pin) *</label>
            <div class="ubicacion-row">
              <input
                type="text"
                id="ubicacion"
                name="ubicacion"
                class="input-mobile"
                placeholder="Ej. Av. Independencia, Centro, Veracruz"
                maxlength="150"
                required
              />
              <button type="button" class="btn-gps" id="btnUbicacionActual" title="Usar mi GPS">
                📍 Mi ubicación
              </button>
            </div>
            <span class="helper-text" id="direccionDetectada">
              Presiona Enter tras escribir una calle para ubicarla en el mapa, o arrastra el marcador rojo.
            </span>
            <div id="mapaReporte"></div>
          </div>

          <div class="form-group">
            <label for="descripcion">Descripción del problema</label>
            <textarea
              id="descripcion"
              name="descripcion"
              class="input-mobile"
              rows="3"
              maxlength="1000"
              placeholder="Describe brevemente el problema y referencias cercanas..."
            ></textarea>
            <span class="helper-text" id="contadorChars">0 / 1000 caracteres</span>
          </div>

          <div class="form-group">
            <label for="evidencia">Evidencia fotográfica (1 a 3 fotos, máx. 8 MB c/u) *</label>
            <input
              type="file"
              id="evidencia"
              name="evidencia[]"
              class="input-mobile"
              accept="image/jpeg,image/png,image/webp"
              multiple
              required
            />
            <span class="helper-text">Formatos permitidos: JPG, PNG o WEBP.</span>
            <div id="previewFotos" class="preview-grid"></div>
          </div>

          <button type="submit" class="btn-submit" id="btnEnviarReporte">
            Enviar Reporte
          </button>
        </form>
      </section>
    </div>
  </main>

  <script>
    const API_REPORTES = '../index.php/api/reportes';
    const LIMITE_REPORTES_VISIBLES = 5;

    let cacheReportesPublicos = [];
    let mostrarTodosLosReportes = false;
    let archivosValidados = [];

    function escapeHTML(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function claseEstatus(estatus) {
      const st = (estatus || '').toLowerCase();
      if (st === 'en revision') return 'st-revision';
      if (st === 'en proceso') return 'st-proceso';
      if (st === 'resuelto') return 'st-resuelto';
      return 'st-recibido';
    }

    // --- 1. LISTADO DE REPORTES (MÁXIMO 5 POR DEFECTO) ---
    async function cargarReportesBD() {
      const contenedor = document.getElementById('listaReportes');
      const pill = document.getElementById('contadorVisibles');

      try {
        const res = await fetch(`${API_REPORTES}?publico=1`);
        const data = await res.json();
        cacheReportesPublicos = Array.isArray(data) ? data : [];
        renderizarReportesRecientes();
      } catch (err) {
        console.error('Error cargando reportes:', err);
        pill.textContent = 'Sin conexión';
        contenedor.innerHTML = '<p style="color:#dc2626;">No se pudieron cargar los reportes recientes.</p>';
      }
    }

    function renderizarReportesRecientes() {
      const contenedor = document.getElementById('listaReportes');
      const pill = document.getElementById('contadorVisibles');
      const total = cacheReportesPublicos.length;

      if (total === 0) {
        pill.textContent = '0 reportes';
        contenedor.innerHTML = '<p style="color:var(--muted,#6b7280);">Aún no hay reportes registrados.</p>';
        return;
      }

      const visibles = mostrarTodosLosReportes
        ? cacheReportesPublicos
        : cacheReportesPublicos.slice(0, LIMITE_REPORTES_VISIBLES);

      pill.textContent = `Mostrando ${visibles.length} de ${total}`;

      const tarjetasHTML = visibles.map(rep => `
        <article class="report-card">
          <div class="report-card-top">
            <strong style="color:var(--text,#111827);">${escapeHTML(rep.folio || '#' + rep.id)}</strong>
            <span class="badge-st ${claseEstatus(rep.estatus)}">${escapeHTML(rep.estatus || 'reporte recibido')}</span>
          </div>
          <div style="font-size:0.82rem; font-weight:700; color:var(--primary,#691c32); text-transform:capitalize; margin-bottom:4px;">
            ${escapeHTML(rep.categoria || 'otro')} · <span style="font-weight:400; color:#6b7280;">${escapeHTML(rep.fecha_creacion || '')}</span>
          </div>
          <p style="margin:0 0 8px; font-size:0.9rem; color:#374151; overflow-wrap:anywhere;">
            ${escapeHTML(rep.titulo || 'Reporte ciudadano')}
          </p>
          <a href="Reporte.php?folio=${encodeURIComponent(rep.folio || '')}" style="font-size:0.85rem; font-weight:700; color:var(--primary,#691c32); text-decoration:none;">
            Ver detalle y seguimiento →
          </a>
        </article>
      `).join('');

      const botonToggle = total > LIMITE_REPORTES_VISIBLES
        ? `<button type="button" id="btnToggleReportes" class="btn-toggle-lista">
            ${mostrarTodosLosReportes
              ? 'Mostrar solo los 5 más recientes'
              : `Ver todos los reportes (${total})`}
           </button>`
        : '';

      contenedor.innerHTML = tarjetasHTML + botonToggle;

      const btnToggle = document.getElementById('btnToggleReportes');
      if (btnToggle) {
        btnToggle.addEventListener('click', () => {
          mostrarTodosLosReportes = !mostrarTodosLosReportes;
          renderizarReportesRecientes();
        });
      }
    }

    // --- 2. VALIDACIÓN DE COSTA DE VERACRUZ (EVITAR MAR) ---
    function estaEnElMar(lat, lng) {
      if (lat < 19.0000 || lat > 19.3000 || lng < -96.3500 || lng > -95.9500) {
        return true;
      }
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
          const lngCosta = lng1 + t * (lng2 - lng1);
          return lng > lngCosta;
        }
      }
      return false;
    }

    // --- 3. MAPA INTERACTIVO MAPLIBRE + NOMINATIM ---
    const centroVeracruz = [-96.1348, 19.1738];
    const mapa = new maplibregl.Map({
      container: 'mapaReporte',
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
      center: centroVeracruz,
      zoom: 13
    });

    const marcador = new maplibregl.Marker({ color: '#691c32', draggable: true })
      .setLngLat(centroVeracruz)
      .addTo(mapa);

    const inputUbicacion = document.getElementById('ubicacion');
    const lblDireccion = document.getElementById('direccionDetectada');

    function fijarCoordenadas(lat, lng, etiqueta = '') {
      if (estaEnElMar(lat, lng)) {
        lblDireccion.style.color = '#dc2626';
        lblDireccion.textContent = '⚠️ El punto seleccionado cae en el mar o fuera de Veracruz. Arrastra el pin a tierra firme.';
        return false;
      }
      marcador.setLngLat([lng, lat]);
      mapa.flyTo({ center: [lng, lat], zoom: 16 });
      inputUbicacion.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
      lblDireccion.style.color = '#166534';
      lblDireccion.textContent = etiqueta
        ? `📍 Ubicación fijada: ${etiqueta}`
        : `📍 Coordenadas fijadas: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
      return true;
    }

    marcador.on('dragend', () => {
      const pos = marcador.getLngLat();
      fijarCoordenadas(pos.lat, pos.lng);
    });

    mapa.on('click', (e) => {
      fijarCoordenadas(e.lngLat.lat, e.lngLat.lng);
    });

    inputUbicacion.addEventListener('keydown', async (e) => {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      const q = inputUbicacion.value.trim();
      if (!q || /^-?\d+\.\d+,\s*-?\d+\.\d+$/.test(q)) return;

      lblDireccion.style.color = '#6b7280';
      lblDireccion.textContent = 'Buscando dirección en Veracruz...';
      try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(q + ', Veracruz, Mexico')}`;
        const res = await fetch(url);
        const resultados = await res.json();
        if (resultados && resultados.length > 0) {
          const lat = parseFloat(resultados[0].lat);
          const lng = parseFloat(resultados[0].lon);
          fijarCoordenadas(lat, lng, resultados[0].display_name);
        } else {
          lblDireccion.style.color = '#d97706';
          lblDireccion.textContent = 'No se encontró esa calle. Puedes mover el pin rojo directamente en el mapa.';
        }
      } catch (_) {
        lblDireccion.textContent = 'No se pudo consultar el mapa en línea; mueve el pin manualmente.';
      }
    });

    document.getElementById('btnUbicacionActual').addEventListener('click', () => {
      if (!navigator.geolocation) {
        alert('Tu navegador no soporta geolocalización.');
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (pos) => fijarCoordenadas(pos.coords.latitude, pos.coords.longitude, 'Ubicación GPS actual'),
        () => alert('No se pudo obtener tu ubicación actual.'),
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });

    // --- 4. CONTADOR DE CARACTERES ---
    const txtDesc = document.getElementById('descripcion');
    const lblChars = document.getElementById('contadorChars');
    txtDesc.addEventListener('input', () => {
      lblChars.textContent = `${txtDesc.value.length} / 1000 caracteres`;
    });

    // --- 5. VALIDACIÓN BINARIA DE IMÁGENES (JPG, PNG, WEBP) ---
    async function esImagenBinariaValida(file) {
      if (file.size > 8 * 1024 * 1024) return false;
      const buffer = await file.slice(0, 12).arrayBuffer();
      const bytes = new Uint8Array(buffer);

      // JPEG: FF D8 FF
      const esJpg = bytes[0] === 0xFF && bytes[1] === 0xD8 && bytes[2] === 0xFF;
      // PNG: 89 50 4E 47
      const esPng = bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4E && bytes[3] === 0x47;
      // WEBP: RIFF....WEBP
      const esWebp = bytes[0] === 0x52 && bytes[1] === 0x49 && bytes[2] === 0x46 && bytes[3] === 0x46 &&
                     bytes[8] === 0x57 && bytes[9] === 0x45 && bytes[10] === 0x42 && bytes[11] === 0x50;

      return esJpg || esPng || esWebp;
    }

    const inputEvidencia = document.getElementById('evidencia');
    const previewFotos = document.getElementById('previewFotos');

    inputEvidencia.addEventListener('change', async () => {
      previewFotos.innerHTML = '';
      archivosValidados = [];
      const seleccionados = Array.from(inputEvidencia.files || []);

      if (seleccionados.length > 3) {
        alert('Solo puedes subir máximo 3 fotografías por reporte.');
        inputEvidencia.value = '';
        return;
      }

      for (const archivo of seleccionados) {
        const valido = await esImagenBinariaValida(archivo);
        if (!valido) {
          alert(`El archivo "${archivo.name}" no es una imagen JPG/PNG/WEBP válida o supera los 8 MB.`);
          inputEvidencia.value = '';
          previewFotos.innerHTML = '';
          archivosValidados = [];
          return;
        }
        archivosValidados.push(archivo);

        const img = document.createElement('img');
        img.src = URL.createObjectURL(archivo);
        img.alt = archivo.name;
        previewFotos.appendChild(img);
      }
    });

    // --- 6. ENVÍO DEL FORMULARIO ---
    const formReporte = document.getElementById('formReporte');
    const alertaForm = document.getElementById('alertaFormulario');
    const btnEnviar = document.getElementById('btnEnviarReporte');

    function mostrarAlerta(tipo, html) {
      alertaForm.className = `alerta-form ${tipo === 'ok' ? 'alerta-ok' : 'alerta-err'}`;
      alertaForm.innerHTML = html;
      alertaForm.style.display = 'block';
    }

    formReporte.addEventListener('submit', async (e) => {
      e.preventDefault();
      alertaForm.style.display = 'none';

      const ubicacionVal = inputUbicacion.value.trim();
      const matchCoords = ubicacionVal.match(/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/);
      if (matchCoords && estaEnElMar(parseFloat(matchCoords[1]), parseFloat(matchCoords[2]))) {
        mostrarAlerta('err', 'La ubicación seleccionada cae en el mar. Coloca el marcador en tierra firme.');
        return;
      }

      if (archivosValidados.length === 0 || archivosValidados.length > 3) {
        mostrarAlerta('err', 'Debes adjuntar entre 1 y 3 fotografías válidas.');
        return;
      }

      btnEnviar.disabled = true;
      btnEnviar.textContent = 'Enviando reporte...';

      const fd = new FormData();
      fd.append('tipo_incidencia', document.getElementById('tipo_incidencia').value);
      fd.append('ubicacion', ubicacionVal);
      fd.append('descripcion', txtDesc.value.trim());
      archivosValidados.forEach(file => fd.append('evidencia[]', file));

      try {
        const res = await fetch(API_REPORTES, { method: 'POST', body: fd });
        const data = await res.json();

        if (res.ok) {
          mostrarAlerta(
            'ok',
            `✅ Reporte registrado con éxito. Folio: <strong>${escapeHTML(data.folio)}</strong> · ` +
            `<a href="Reporte.php?folio=${encodeURIComponent(data.folio)}" style="color:#166534; text-decoration:underline;">Ver seguimiento</a>`
          );
          formReporte.reset();
          previewFotos.innerHTML = '';
          archivosValidados = [];
          lblChars.textContent = '0 / 1000 caracteres';
          lblDireccion.style.color = '#6b7280';
          lblDireccion.textContent = 'Presiona Enter tras escribir una calle para ubicarla en el mapa, o arrastra el marcador rojo.';
          await cargarReportesBD();
        } else {
          mostrarAlerta('err', escapeHTML(data.error || 'No se pudo registrar el reporte.'));
        }
      } catch (err) {
        mostrarAlerta('err', 'Error de conexión con el servidor.');
      } finally {
        btnEnviar.disabled = false;
        btnEnviar.textContent = 'Enviar Reporte';
      }
    });

    document.addEventListener('DOMContentLoaded', cargarReportesBD);
  </script>

  <?php include __DIR__ . '/chat_ia.php'; ?>
</body>
</html>

<?php
  $widgetIA = __DIR__ . '/chat_ia.php';
  if (file_exists($widgetIA)) {
      include $widgetIA;
  }
  ?>
</body>