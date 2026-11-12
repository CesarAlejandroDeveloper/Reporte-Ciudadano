<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Detalle de Reporte - CiudadReporta</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
  <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
  <style>
    .detalle-wrapper {
      max-width: 1040px;
      margin: 0 auto;
      padding: 20px;
    }
    .detalle-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      padding-bottom: 16px;
      margin-bottom: 22px;
      border-bottom: 2px solid var(--border, #e5e7eb);
    }
    .detalle-header h1 {
      margin: 0;
      color: var(--primary, #691c32);
      font-size: 1.5rem;
    }
    .header-acciones {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .btn-nav {
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
    .btn-nav-primary {
      background: var(--primary, #691c32);
      color: #ffffff;
      border-color: var(--primary, #691c32);
    }

    /* Cartel de estado final (Resuelto / Dado de baja) */
    .cartel-estado {
      display: none;
      padding: 14px 18px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-weight: 700;
      font-size: 0.98rem;
    }
    .cartel-resuelto {
      display: block;
      background: #dcfce7;
      color: #166534;
      border: 1px solid #86efac;
      border-left: 6px solid #16a34a;
    }
    .cartel-baja {
      display: block;
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
      border-left: 6px solid #dc2626;
    }

    .card-bloque {
      background: var(--panel, #ffffff);
      border: 1px solid var(--border, #e5e7eb);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
    }
    .card-bloque h2 {
      margin: 0 0 14px;
      font-size: 1.12rem;
      color: var(--primary, #691c32);
      border-bottom: 1px solid var(--border, #e5e7eb);
      padding-bottom: 8px;
    }

    /* Línea de tiempo */
    .timeline {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      margin-top: 8px;
    }
    @media (max-width: 720px) {
      .timeline {
        grid-template-columns: 1fr;
      }
    }
    .paso-tl {
      border: 1px solid #e5e7eb;
      background: #f9fafb;
      border-radius: 10px;
      padding: 12px;
      position: relative;
      opacity: 0.65;
    }
    .paso-tl.completado {
      opacity: 1;
      background: #fdf2f4;
      border-color: var(--primary, #691c32);
    }
    .paso-tl.actual {
      opacity: 1;
      background: var(--primary, #691c32);
      color: #ffffff;
      border-color: var(--primary, #691c32);
    }
    .paso-tl strong {
      display: block;
      font-size: 0.92rem;
      margin-bottom: 4px;
    }
    .paso-tl span {
      font-size: 0.78rem;
      opacity: 0.85;
    }

    .detalle-grid {
      display: grid;
      grid-template-columns: 1.1fr 1fr;
      gap: 20px;
    }
    @media (max-width: 820px) {
      .detalle-grid {
        grid-template-columns: 1fr;
      }
    }
    .meta-lista {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      margin-bottom: 14px;
    }
    .meta-item {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 10px 12px;
    }
    .meta-item span {
      display: block;
      font-size: 0.78rem;
      color: var(--muted, #6b7280);
      font-weight: 600;
    }
    .meta-item strong {
      font-size: 0.95rem;
      color: var(--text, #111827);
      text-transform: capitalize;
    }
    .descripcion-caja {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 12px 14px;
      font-size: 0.94rem;
      line-height: 1.55;
      white-space: pre-wrap;
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    #mapaDetalle {
      width: 100%;
      height: 270px;
      border-radius: 10px;
      border: 1px solid #d1d5db;
    }
    .galeria-evidencias {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
    }
    .galeria-evidencias img {
      width: 100%;
      height: 190px;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid #d1d5db;
      cursor: pointer;
      transition: transform 0.15s ease;
    }
    .galeria-evidencias img:hover {
      transform: scale(1.02);
    }
    .error-pantalla {
      text-align: center;
      padding: 40px 20px;
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
    }
  </style>
</head>
<body>
  <main class="detalle-wrapper">
    <header class="detalle-header">
      <div>
        <h1 id="tituloFolio">Seguimiento de Reporte</h1>
        <span id="subtituloFecha" style="color: var(--muted, #6b7280); font-size: 0.9rem;">
          Consultando información...
        </span>
      </div>
      <div class="header-acciones">
        <a href="Pagina.php" class="btn-nav btn-nav-primary">← Volver a reportes</a>
        <a href="Home.php" class="btn-nav">Inicio</a>
      </div>
    </header>

    <div id="contenedorError" class="error-pantalla" style="display: none;">
      <h2 style="color: #dc2626; margin-top: 0;">No se pudo abrir el reporte</h2>
      <p id="mensajeErrorDetalle" style="color: #4b5563;"></p>
      <a href="Pagina.php" class="btn-nav btn-nav-primary">Ir al portal de reportes</a>
    </div>

    <div id="contenidoReporte" style="display: none;">
      <div id="cartelEstado" class="cartel-estado"></div>

      <!-- LÍNEA DE TIEMPO -->
      <section class="card-bloque">
        <h2>Línea de Tiempo del Seguimiento</h2>
        <div class="timeline" id="lineaTiempo">
          <div class="paso-tl" data-paso="reporte recibido">
            <strong>1. Reporte Recibido</strong>
            <span>Registrado en plataforma</span>
          </div>
          <div class="paso-tl" data-paso="en revision">
            <strong>2. En Revisión</strong>
            <span>Validación por área técnica</span>
          </div>
          <div class="paso-tl" data-paso="en proceso">
            <strong>3. En Proceso</strong>
            <span>Cuadrilla asignada en atención</span>
          </div>
          <div class="paso-tl" data-paso="resuelto">
            <strong>4. Resuelto</strong>
            <span>Incidencia atendida y cerrada</span>
          </div>
        </div>
      </section>

      <!-- INFORMACIÓN Y MAPA -->
      <div class="detalle-grid">
        <section class="card-bloque">
          <h2>Información del Reporte</h2>
          <div class="meta-lista">
            <div class="meta-item">
              <span>Folio</span>
              <strong id="detFolio" style="text-transform: uppercase;">-</strong>
            </div>
            <div class="meta-item">
              <span>Categoría</span>
              <strong id="detCategoria">-</strong>
            </div>
            <div class="meta-item">
              <span>Prioridad</span>
              <strong id="detPrioridad">-</strong>
            </div>
            <div class="meta-item">
              <span>Estatus Actual</span>
              <strong id="detEstatus">-</strong>
            </div>
          </div>

          <label style="display:block; font-weight:700; font-size:0.88rem; color:var(--primary,#691c32); margin-bottom:6px;">
            Título y Descripción
          </label>
          <div class="descripcion-caja" id="detDescripcion"></div>
        </section>

        <section class="card-bloque">
          <h2>Ubicación Reportada</h2>
          <div id="mapaDetalle"></div>
          <p id="textoCoords" style="margin: 8px 0 0; font-size: 0.84rem; color: var(--muted, #6b7280);"></p>
        </section>
      </div>

      <!-- GALERÍA DE EVIDENCIAS -->
      <section class="card-bloque">
        <h2>Evidencia Fotográfica</h2>
        <div id="galeriaEvidencias" class="galeria-evidencias"></div>
      </section>
    </div>
  </main>

  <script>
    const API_REPORTES = '../index.php/api/reportes';

    function obtenerListaFotos(fotoUrl) {
      if (!fotoUrl) return [];
      let lista = [];
      if (typeof fotoUrl === 'string' && fotoUrl.trim().startsWith('[')) {
        try {
          lista = JSON.parse(fotoUrl);
        } catch (_) {
          lista = [fotoUrl];
        }
      } else if (Array.isArray(fotoUrl)) {
        lista = fotoUrl;
      } else {
        lista = [fotoUrl];
      }

      return lista.filter(Boolean).map(ruta => {
        if (ruta.startsWith('http') || ruta.startsWith('../')) return ruta;
        if (ruta.startsWith('api/')) return '../../' + ruta;
        return '../' + ruta;
      });
    }

    function mostrarPantallaError(mensaje) {
      document.getElementById('contenidoReporte').style.display = 'none';
      document.getElementById('contenedorError').style.display = 'block';
      document.getElementById('mensajeErrorDetalle').textContent = mensaje;
    }

    function actualizarLineaTiempo(estatusActual, historial) {
      const orden = ['reporte recibido', 'en revision', 'en proceso', 'resuelto'];
      const st = (estatusActual || 'reporte recibido').toLowerCase();
      const indiceActual = orden.indexOf(st);

      const fechasPorEstatus = {};
      if (Array.isArray(historial)) {
        historial.forEach(item => {
          const clave = (item.estatus || '').toLowerCase();
          if (clave && item.creado) {
            fechasPorEstatus[clave] = item.creado;
          }
        });
      }

      document.querySelectorAll('#lineaTiempo .paso-tl').forEach((pasoEl, idx) => {
        const clavePaso = pasoEl.dataset.paso;
        pasoEl.classList.remove('completado', 'actual');

        if (indiceActual !== -1) {
          if (idx < indiceActual) {
            pasoEl.classList.add('completado');
          } else if (idx === indiceActual) {
            pasoEl.classList.add('actual');
          }
        }

        if (fechasPorEstatus[clavePaso]) {
          const span = pasoEl.querySelector('span');
          if (span) span.textContent = fechasPorEstatus[clavePaso];
        }
      });
    }

    async function cargarDetalleReporte() {
      const params = new URLSearchParams(window.location.search);
      const folio = (params.get('folio') || '').trim();
      const id = (params.get('id') || '').trim();

      if (!folio && !id) {
        mostrarPantallaError('No se especificó ningún folio o identificador de reporte en la URL.');
        return;
      }

      const queryUrl = folio
        ? `${API_REPORTES}?folio=${encodeURIComponent(folio)}`
        : `${API_REPORTES}?id=${encodeURIComponent(id)}`;

      try {
        const res = await fetch(queryUrl);
        const data = await res.json();

        if (!res.ok || !data || data.error) {
          mostrarPantallaError(data.error || 'El reporte solicitado no existe o no está disponible.');
          return;
        }

        document.getElementById('contenidoReporte').style.display = 'block';

        const folioTexto = data.folio || ('#' + data.id);
        const estatus = (data.estatus || 'reporte recibido').toLowerCase();

        document.getElementById('tituloFolio').textContent = `Reporte ${folioTexto}`;
        document.getElementById('subtituloFecha').textContent = `Registrado el: ${data.fecha_creacion || 'Fecha no disponible'}`;

        document.getElementById('detFolio').textContent = folioTexto;
        document.getElementById('detCategoria').textContent = data.categoria || 'Otro';
        document.getElementById('detPrioridad').textContent = data.prioridad || 'Media';
        document.getElementById('detEstatus').textContent = estatus === 'denegada' ? 'Dado de baja' : estatus;

        const descCompleta = data.titulo && !(data.descripcion || '').includes(data.titulo)
          ? `${data.titulo}\n\n${data.descripcion || ''}`
          : (data.descripcion || data.titulo || 'Sin descripción.');
        document.getElementById('detDescripcion').textContent = descCompleta;

        // Cartel si está resuelto o dado de baja
        const cartel = document.getElementById('cartelEstado');
        if (estatus === 'resuelto') {
          cartel.className = 'cartel-estado cartel-resuelto';
          cartel.textContent = '✅ Reporte Resuelto: Esta incidencia ya fue atendida por el área correspondiente.';
        } else if (estatus === 'denegada' || estatus === 'cancelado') {
          cartel.className = 'cartel-estado cartel-baja';
          cartel.textContent = '⚠️ Reporte Dado de Baja: Este reporte fue cerrado o descartado por el administrador.';
        }

        actualizarLineaTiempo(estatus, data.historial || []);

        // Mapa
        const centroVeracruz = [-96.1348, 19.1738];
        const tieneCoords = data.latitud && data.longitud && !isNaN(parseFloat(data.latitud)) && !isNaN(parseFloat(data.longitud));
        const coords = tieneCoords
          ? [parseFloat(data.longitud), parseFloat(data.latitud)]
          : centroVeracruz;

        document.getElementById('textoCoords').textContent = tieneCoords
          ? `Coordenadas: ${parseFloat(data.latitud).toFixed(6)}, ${parseFloat(data.longitud).toFixed(6)}`
          : 'Este reporte no cuenta con coordenadas GPS exactas; se muestra el centro de Veracruz.';

        const mapa = new maplibregl.Map({
          container: 'mapaDetalle',
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
          center: coords,
          zoom: tieneCoords ? 16 : 13
        });

        if (tieneCoords) {
          new maplibregl.Marker({ color: '#691c32' }).setLngLat(coords).addTo(mapa);
        }

        // Galería de fotos
        const galeria = document.getElementById('galeriaEvidencias');
        const fotos = obtenerListaFotos(data.foto_url);
        if (fotos.length > 0) {
          galeria.innerHTML = fotos.map((src, i) => `
            <img src="${src}" alt="Evidencia ${i + 1}" onclick="window.open('${src}', '_blank', 'noopener')" />
          `).join('');
        } else {
          galeria.innerHTML = '<p style="color: var(--muted, #6b7280);">Este reporte no tiene fotografías adjuntas.</p>';
        }

      } catch (err) {
        console.error('Error cargando detalle:', err);
        mostrarPantallaError('Error de conexión con el servidor al consultar el reporte.');
      }
    }

    document.addEventListener('DOMContentLoaded', cargarDetalleReporte);
  </script>

  <?php
  $widgetIA = __DIR__ . '/chat_ia.php';
  if (file_exists($widgetIA)) {
      include $widgetIA;
  }
  ?>
</body>
</html>