<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>CiudadReporta - Obras Públicas</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
  <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
  <script src="https://unpkg.com/pmtiles@2.13.0/dist/index.js"></script>

  <style>
    body {
      padding: 10px;
      -webkit-tap-highlight-color: transparent;
    }
    .form-group label {
      font-size: 0.95rem;
      font-weight: 600;
    }
    .input-mobile {
      padding: 12px;
      font-size: 1rem;
      border-radius: 8px;
    }
    .report-card h3 {
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .grid-foto-opciones {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 4px;
    }
    .btn-media-opcion {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 14px 10px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 0.95rem;
      color: #ffffff;
      border: none;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: transform 0.1s, opacity 0.2s;
    }
    .btn-media-opcion:active {
      transform: scale(0.97);
      opacity: 0.9;
    }
    .btn-camara-directa { background: #2563eb; }
    .btn-galeria-directa { background: #4b5563; }

    .preview-contenedor {
      display: none;
      margin-top: 12px;
      padding: 12px;
      background: #f8fafc;
      border-radius: 8px;
      border: 1px solid var(--border, #cbd5e1);
    }
    .preview-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--primary, #691c32);
    }
    .grid-previews {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }
    @media (max-width: 540px) {
      .grid-previews {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    .preview-item {
      position: relative;
      background: #fff;
      border: 1px solid var(--border, #cbd5e1);
      border-radius: 8px;
      padding: 6px;
      text-align: center;
    }
    .preview-item img {
      width: 100%;
      height: 110px;
      object-fit: cover;
      border-radius: 6px;
      display: block;
    }
    .preview-item small {
      display: block;
      margin-top: 4px;
      font-size: 0.75rem;
      color: var(--muted, #64748b);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .btn-quitar-item {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(220, 38, 38, 0.92);
      color: #fff;
      border: none;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      font-weight: 700;
      font-size: 0.8rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .alerta-mapa {
      display: none;
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-top: 6px;
    }
  </style>
</head>
<body>
  <main class="layout-grid">
    <aside class="panel panel-left">
      <h2>Reportes Recientes</h2>
      <div class="report-list" id="contenedorReportes">
        <p style="color: var(--muted); font-size: 0.9rem;">Cargando reportes...</p>
      </div>
    </aside>

    <section class="panel panel-right">
      <h2>Levantar un Reporte</h2>
      <span class="subtitle">Dirigido a: Dirección de Obras Públicas y Desarrollo Urbano</span>
      
      <form id="formularioReporte" enctype="multipart/form-data">
        <div class="form-row">
          <div class="form-group">
            <label for="tipo_incidencia">Tipo de incidencia</label>
            <select id="tipo_incidencia" name="tipo_incidencia" class="input-mobile" required>
              <option value="">Selecciona una opción...</option>
              <option value="bache">Bache en vialidad</option>
              <option value="luminaria">Luminaria fundida / farol</option>
              <option value="accidente">Daño por accidente vial</option>
              <option value="fuga">Fuga de agua</option>
              <option value="otro">Otro</option>
            </select>
          </div>

          <div class="form-group">
            <label for="ubicacion">Ubicación exacta (Escribe y presiona Enter)</label>
            <div style="display: flex; gap: 8px;">
              <input type="text" id="ubicacion" name="ubicacion" class="input-mobile" maxlength="150" placeholder="Ej: Av. Díaz Mirón, Veracruz" required style="flex: 1;">
              <button type="button" id="btnToggleMapa" title="Seleccionar en el mapa" class="btn" style="width: auto; padding: 0 16px; display: flex; align-items: center; justify-content: center; gap: 4px; white-space: nowrap;">
                📍 <span>Mapa</span>
              </button>
            </div>
          </div>
        </div>

        <div id="contenedorMapa" style="display: none; margin-bottom: 16px;">
          <label style="font-size: 0.85rem; color: var(--muted, #666); margin-bottom: 4px; display: block;">Toca el mapa (en tierra firme) para marcar el punto exacto:</label>
          <div id="mapa" style="height: 260px; width: 100%; border-radius: 8px; border: 1px solid var(--border, #ccc);"></div>
          <div id="alertaMapa" class="alerta-mapa">⚠️ No puedes seleccionar el mar. Ubica el marcador dentro de tierra firme en Veracruz.</div>
        </div>

        <div class="form-group">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <label for="descripcion">Descripción del problema</label>
            <small id="contadorChars" style="color: var(--muted); font-size: 0.8rem;">0 / 1000</small>
          </div>
          <textarea id="descripcion" name="descripcion" class="input-mobile" maxlength="1000" placeholder="Describe brevemente el problema..."></textarea>
        </div>

        <!-- FOTOGRAFÍAS DE EVIDENCIA (HASTA 3 IMÁGENES) -->
        <div class="form-group">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <label>Fotografías de Evidencia (JPG, PNG o WEBP)</label>
            <small id="contadorFotosLabel" style="color: var(--muted); font-size: 0.8rem;">0 / 3 imágenes</small>
          </div>

          <input type="file" id="inputCamara" accept="image/jpeg,image/png,image/webp" capture="environment" style="display: none;">
          <input type="file" id="inputGaleria" accept="image/jpeg,image/png,image/webp" multiple style="display: none;">

          <div class="grid-foto-opciones">
            <button type="button" id="btnAbrirCamara" class="btn-media-opcion btn-camara-directa">
              📷 <span>Tomar Foto</span>
            </button>
            <button type="button" id="btnAbrirGaleria" class="btn-media-opcion btn-galeria-directa">
              🖼️ <span>Subir Galería (hasta 3)</span>
            </button>
          </div>

          <div class="preview-contenedor" id="contenedorPreview">
            <div class="preview-header">
              <span id="tituloPreviewFotos">Evidencias seleccionadas (0 / 3)</span>
              <button type="button" id="btnLimpiarTodasFotos" style="background: none; border: none; color: #dc2626; font-weight: 700; cursor: pointer; font-size: 0.82rem;">Limpiar todas</button>
            </div>
            <div class="grid-previews" id="gridPreviews"></div>
          </div>
        </div>

        <button type="submit" id="btnEnviarReporte" class="btn" style="padding: 16px; font-size: 1.05rem; font-weight: 700; margin-top: 10px;">Enviar reporte ciudadano</button>
      </form>
    </section>
  </main>

  <script>
    const API_URL = '../index.php/api/reportes';
    const MAX_FOTOS = 3;

    function escapeHTML(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    const txtDescripcion = document.getElementById('descripcion');
    const contadorChars = document.getElementById('contadorChars');
    txtDescripcion.addEventListener('input', () => {
      contadorChars.innerText = `${txtDescripcion.value.length} / 1000`;
    });

    // --- 1. GESTIÓN DE HASTA 3 IMÁGENES CON VALIDACIÓN BINARIA ---
    let archivosSeleccionados = [];

    const inputCamara = document.getElementById('inputCamara');
    const inputGaleria = document.getElementById('inputGaleria');
    const contenedorPreview = document.getElementById('contenedorPreview');
    const gridPreviews = document.getElementById('gridPreviews');
    const contadorFotosLabel = document.getElementById('contadorFotosLabel');
    const tituloPreviewFotos = document.getElementById('tituloPreviewFotos');

    document.getElementById('btnAbrirCamara').addEventListener('click', () => {
      if (archivosSeleccionados.length >= MAX_FOTOS) {
        alert(`Ya alcanzaste el límite de ${MAX_FOTOS} fotografías por reporte.`);
        return;
      }
      inputCamara.click();
    });

    document.getElementById('btnAbrirGaleria').addEventListener('click', () => {
      if (archivosSeleccionados.length >= MAX_FOTOS) {
        alert(`Ya alcanzaste el límite de ${MAX_FOTOS} fotografías por reporte.`);
        return;
      }
      inputGaleria.click();
    });

    function verificarFirmaImagen(archivo) {
      return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onloadend = function(e) {
          if (!e.target.result) return resolve(false);
          const bytes = new Uint8Array(e.target.result);
          const esJpeg = bytes[0] === 0xFF && bytes[1] === 0xD8 && bytes[2] === 0xFF;
          const esPng = bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4E && bytes[3] === 0x47;
          const esWebp = bytes[0] === 0x52 && bytes[1] === 0x49 && bytes[2] === 0x46 && bytes[3] === 0x46 &&
                         bytes[8] === 0x57 && bytes[9] === 0x45 && bytes[10] === 0x42 && bytes[11] === 0x50;
          resolve(esJpeg || esPng || esWebp);
        };
        reader.onerror = () => resolve(false);
        reader.readAsArrayBuffer(archivo.slice(0, 12));
      });
    }

    function leerComoDataURL(archivo) {
      return new Promise((resolve) => {
        const lector = new FileReader();
        lector.onload = (e) => resolve(e.target.result);
        lector.readAsDataURL(archivo);
      });
    }

    async function renderizarPreviewsFotos() {
      const total = archivosSeleccionados.length;
      contadorFotosLabel.innerText = `${total} / ${MAX_FOTOS} imágenes`;
      tituloPreviewFotos.innerText = `Evidencias seleccionadas (${total} / ${MAX_FOTOS})`;

      if (total === 0) {
        contenedorPreview.style.display = 'none';
        gridPreviews.innerHTML = '';
        return;
      }

      contenedorPreview.style.display = 'block';
      gridPreviews.innerHTML = '';

      for (let i = 0; i < total; i++) {
        const file = archivosSeleccionados[i];
        const dataUrl = await leerComoDataURL(file);

        const item = document.createElement('div');
        item.className = 'preview-item';
        item.innerHTML = `
          <button type="button" class="btn-quitar-item" title="Quitar imagen" onclick="quitarFotoIndice(${i})">✕</button>
          <img src="${dataUrl}" alt="Evidencia ${i + 1}" />
          <small>${escapeHTML(file.name)}</small>
        `;
        gridPreviews.appendChild(item);
      }
    }

    window.quitarFotoIndice = function(indice) {
      archivosSeleccionados.splice(indice, 1);
      renderizarPreviewsFotos();
    };

    document.getElementById('btnLimpiarTodasFotos').addEventListener('click', () => {
      archivosSeleccionados = [];
      inputCamara.value = '';
      inputGaleria.value = '';
      renderizarPreviewsFotos();
    });

    async function procesarNuevosArchivos(listaFiles, inputOrigen) {
      if (!listaFiles || listaFiles.length === 0) return;

      const mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
      let avisos = [];

      for (const archivo of Array.from(listaFiles)) {
        if (archivosSeleccionados.length >= MAX_FOTOS) {
          avisos.push(`Solo se permiten máximo ${MAX_FOTOS} fotografías por reporte.`);
          break;
        }

        const esFirmaValida = await verificarFirmaImagen(archivo);
        if (!mimesPermitidos.includes(archivo.type) || !esFirmaValida) {
          avisos.push(`"${archivo.name}" no es una imagen válida (solo JPG, PNG o WEBP).`);
          continue;
        }

        if (archivo.size > 8 * 1024 * 1024) {
          avisos.push(`"${archivo.name}" supera el límite de 8 MB.`);
          continue;
        }

        archivosSeleccionados.push(archivo);
      }

      inputOrigen.value = '';
      await renderizarPreviewsFotos();

      if (avisos.length > 0) {
        alert(avisos.join('\n'));
      }
    }

    inputCamara.addEventListener('change', function() {
      procesarNuevosArchivos(this.files, this);
    });

    inputGaleria.addEventListener('change', function() {
      procesarNuevosArchivos(this.files, this);
    });

    // --- 2. DETECCIÓN DE MAR EN VERACRUZ ---
    function estaEnElMar(lat, lng) {
      if (lat < 19.0000 || lat > 19.3000 || lng < -96.3500 || lng > -95.9500) {
        return true;
      }
      const costa = [
        [19.3000, -96.2100],
        [19.2400, -96.1820],
        [19.2250, -96.1620],
        [19.2120, -96.1480],
        [19.2080, -96.1255],
        [19.1980, -96.1285],
        [19.1860, -96.1185],
        [19.1700, -96.1115],
        [19.1450, -96.1005],
        [19.1250, -96.0955],
        [19.1020, -96.0975],
        [19.0920, -96.0840],
        [19.0750, -96.0390],
        [19.0550, -95.9790],
        [19.0000, -95.9500]
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

    // --- 3. CONFIGURACIÓN DEL MAPA ---
    const centroInicial = [-96.1348, 19.1738];
    let ultimaCoordValida = { lng: centroInicial[0], lat: centroInicial[1] };
    const limitesVeracruz = [
      [-96.3500, 19.0000],
      [-96.0000, 19.3000]
    ];

    let map = null;
    let marker = null;
    const alertaMapa = document.getElementById('alertaMapa');

    function mostrarAvisoMar() {
      alertaMapa.style.display = 'block';
      clearTimeout(alertaMapa._timer);
      alertaMapa._timer = setTimeout(() => {
        alertaMapa.style.display = 'none';
      }, 3500);
    }

    async function validarYActualizarPunto(lngLat) {
      if (estaEnElMar(lngLat.lat, lngLat.lng)) {
        marker.setLngLat([ultimaCoordValida.lng, ultimaCoordValida.lat]);
        mostrarAvisoMar();
        return;
      }

      ultimaCoordValida = { lng: lngLat.lng, lat: lngLat.lat };
      alertaMapa.style.display = 'none';
      document.getElementById('ubicacion').value = `${lngLat.lat.toFixed(6)}, ${lngLat.lng.toFixed(6)}`;

      try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lngLat.lat}&lon=${lngLat.lng}`);
        const data = await res.json();
        const esAgua = data && (
          data.error ||
          data.type === 'sea' ||
          data.type === 'bay' ||
          data.type === 'water' ||
          data.type === 'strait' ||
          (data.class === 'natural' && data.type === 'water')
        );
        if (esAgua) {
          mostrarAvisoMar();
        }
      } catch (_) {}
    }

    function inicializarMapa() {
      if (map) return;

      map = new maplibregl.Map({
        container: 'mapa',
        style: {
          version: 8,
          sources: {
            'osm-tiles': {
              type: 'raster',
              tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
              tileSize: 256,
              attribution: '&copy; OpenStreetMap contributors'
            }
          },
          layers: [
            { id: 'osm-tiles', type: 'raster', source: 'osm-tiles', minzoom: 10, maxzoom: 19 }
          ]
        },
        center: centroInicial,
        zoom: 14,
        maxBounds: limitesVeracruz
      });

      map.addControl(new maplibregl.NavigationControl());

      marker = new maplibregl.Marker({ draggable: true, color: '#e11d48' })
        .setLngLat(centroInicial)
        .addTo(map);

      marker.on('dragend', () => validarYActualizarPunto(marker.getLngLat()));
      map.on('click', (e) => {
        marker.setLngLat(e.lngLat);
        validarYActualizarPunto(e.lngLat);
      });
    }

    // --- 4. BÚSQUEDA AL PRESIONAR ENTER ---
    const inputUbicacion = document.getElementById('ubicacion');
    const contenedorMapa = document.getElementById('contenedorMapa');

    inputUbicacion.addEventListener('keydown', async function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const direccionUsuario = this.value.trim();
        if (!direccionUsuario) return;

        if (contenedorMapa.style.display === 'none') {
          contenedorMapa.style.display = 'block';
        }
        
        inicializarMapa();
        setTimeout(() => map.resize(), 100);

        try {
          let textoBusqueda = direccionUsuario;
          if (!textoBusqueda.toLowerCase().includes('veracruz')) {
            textoBusqueda += ', Veracruz';
          }

          let url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(textoBusqueda)}&countrycodes=mx&bounded=1&viewbox=-96.35,19.30,-96.00,19.00`;
          let respuesta = await fetch(url);
          let resultados = await respuesta.json();

          if (!resultados || resultados.length === 0) {
            const callePrincipal = direccionUsuario.split(',')[0] + ', Veracruz';
            url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(callePrincipal)}&countrycodes=mx&bounded=1&viewbox=-96.35,19.30,-96.00,19.00`;
            respuesta = await fetch(url);
            resultados = await respuesta.json();
          }

          if (resultados && resultados.length > 0) {
            const lat = parseFloat(resultados[0].lat);
            const lon = parseFloat(resultados[0].lon);
            if (!estaEnElMar(lat, lon)) {
              ultimaCoordValida = { lng: lon, lat: lat };
              map.setCenter([lon, lat]);
              map.setZoom(16);
              marker.setLngLat([lon, lat]);
            } else {
              mostrarAvisoMar();
            }
          }
        } catch (error) {
          console.error("Error al buscar la ubicación:", error);
        }
      }
    });

    // --- 5. BOTÓN TOGGLE MAPA ---
    document.getElementById('btnToggleMapa').addEventListener('click', () => {
      if (contenedorMapa.style.display === 'none') {
        contenedorMapa.style.display = 'block';
        inicializarMapa();
        setTimeout(() => map.resize(), 100);
      } else {
        contenedorMapa.style.display = 'none';
      }
    });

    // --- 6. ENVIAR REPORTE CON BLOQUEO ANTI-SPAM Y GUARDADO LOCAL ---
    let enviandoReporte = false;
    const btnEnviarReporte = document.getElementById('btnEnviarReporte');

    document.getElementById('formularioReporte').addEventListener('submit', async function(evento) {
      evento.preventDefault();

      if (enviandoReporte) return;

      if (archivosSeleccionados.length === 0) {
        alert("Por favor adjunta al menos 1 fotografía (máximo 3) en formato JPG, PNG o WEBP como evidencia.");
        return;
      }

      enviandoReporte = true;
      btnEnviarReporte.disabled = true;
      btnEnviarReporte.textContent = 'Enviando reporte...';

      const formularioDatos = new FormData(this);
      archivosSeleccionados.forEach((archivo) => {
        formularioDatos.append('evidencia[]', archivo);
      });

      try {
        const respuesta = await fetch(API_URL, {
          method: 'POST',
          body: formularioDatos 
        });

        const resultado = await respuesta.json();

        if (respuesta.ok) {
          // Extraemos el ID o folio que devuelve tu backend
          const idReporte = resultado.id || resultado.folio;
          
          // GUARDADO LOCAL PARA DESTACARLO LUEGO
          if (idReporte) {
            let misReportes = JSON.parse(localStorage.getItem('misReportes') || '[]');
            if (!misReportes.includes(String(idReporte))) {
              misReportes.push(String(idReporte));
              localStorage.setItem('misReportes', JSON.stringify(misReportes));
            }
          }

          alert(`Reporte guardado con éxito. Folio: ${idReporte} (${resultado.total_fotos || archivosSeleccionados.length} foto(s) adjunta(s)).`);
          this.reset();
          contadorChars.innerText = "0 / 1000";
          document.getElementById('btnLimpiarTodasFotos').click();
          contenedorMapa.style.display = 'none';
          
          await cargarReportesBD(); // Recargar la lista
        } else {
          alert("Aviso: " + (resultado.error || "No se pudo guardar el reporte"));
        }
      } catch (error) {
        console.error("Error de conexión:", error);
        alert("No se pudo conectar con el servidor.");
      } finally {
        enviandoReporte = false;
        btnEnviarReporte.disabled = false;
        btnEnviarReporte.textContent = 'Enviar reporte ciudadano';
      }
    });

    // --- 7. CARGAR REPORTES RECIENTES (ORDENANDO Y DESTACANDO LOS PROPIOS) ---
    async function cargarReportesBD() {
      const contenedorReportes = document.getElementById('contenedorReportes');
      contenedorReportes.innerHTML = '<p style="color: var(--muted); font-size: 0.9rem;">Cargando reportes...</p>';
      
      try {
        const respuesta = await fetch(`${API_URL}?publico=1`);
        const reportesDB = await respuesta.json();
        
        contenedorReportes.innerHTML = '';

        let reportesVisibles = Array.isArray(reportesDB)
          ? reportesDB.filter(r => !['denegada', 'cancelado'].includes((r.estatus || '').toLowerCase()))
          : [];

        if (reportesVisibles.length === 0) {
          contenedorReportes.innerHTML = '<p style="color: var(--muted); font-size: 0.9rem;">Aún no hay reportes activos registrados.</p>';
          return;
        }

        // Recuperar los IDs guardados en este dispositivo
        const misReportes = JSON.parse(localStorage.getItem('misReportes') || '[]');

        // Separar "mis reportes" y "los demás"
        const reportesPropios = [];
        const reportesAjenos = [];

        reportesVisibles.forEach(reporte => {
          if (misReportes.includes(String(reporte.id || reporte.folio))) {
            reportesPropios.push(reporte);
          } else {
            reportesAjenos.push(reporte);
          }
        });

        // Unir los arreglos, dejando los propios hasta arriba
        const reportesOrdenados = [...reportesPropios, ...reportesAjenos];

        reportesOrdenados.forEach(reporte => {
          const tituloSeguro = escapeHTML(reporte.titulo || reporte.categoria || 'Reporte Ciudadano');
          const fechaSegura = escapeHTML(reporte.fecha_creacion);
          const estatusSeguro = escapeHTML(reporte.estatus);
          
          // Verificar si el reporte es propio para agregarle el badge visual
          const esMio = misReportes.includes(String(reporte.id || reporte.folio));
          const badgePropio = esMio 
            ? `<span style="font-size: 0.7rem; background: var(--primary, #75203D); color: white; padding: 2px 6px; border-radius: 4px; margin-left: 6px; vertical-align: middle;">Tu reporte</span>` 
            : '';

          const tarjeta = `
            <a href="Reporte.php?id=${encodeURIComponent(reporte.id || reporte.folio)}" style="text-decoration: none; color: inherit;">
              <div class="report-card" style="cursor: pointer; ${esMio ? 'border-left: 4px solid var(--primary, #75203D); background: #fdfafb;' : ''}">
                <h3 style="text-transform: capitalize; display: flex; align-items: center; justify-content: space-between;">
                  ${tituloSeguro} 
                  ${badgePropio}
                </h3>
                <span class="report-date">${fechaSegura}</span>
                <span class="report-status">${estatusSeguro}</span>
              </div>
            </a>
          `;
          contenedorReportes.innerHTML += tarjeta;
        });

      } catch (error) {
        console.error("Error cargando los reportes:", error);
        contenedorReportes.innerHTML = '<p style="color: var(--muted); font-size: 0.9rem;">No se pudieron cargar los reportes. Verifica la conexión a la base de datos.</p>';
      }
    }

    document.addEventListener("DOMContentLoaded", cargarReportesBD);
  </script>
</body>
</html>