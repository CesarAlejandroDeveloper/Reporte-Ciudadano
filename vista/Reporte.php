<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Detalle de Reporte - CiudadReporta</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <!-- Librería de mapa para la vista individual -->
  <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
  <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
  <style>
    .detalle-container { max-width: 800px; margin: 0 auto; }
    .header-detalle { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
    .dato-grupo { margin-bottom: 20px; }
    .dato-label { font-weight: 600; color: var(--primary); font-size: 0.95rem; margin-bottom: 4px; display: block; }
    .dato-valor { font-size: 1.05rem; color: var(--text); background: #fafafa; padding: 12px; border-radius: 6px; border: 1px solid var(--border); white-space: pre-line; }
    .foto-evidencia { max-width: 100%; height: auto; border-radius: 8px; border: 1px solid var(--border); margin-top: 10px; }
    #contenedorResolucion { display: none; }
    .btn-volver { text-align: center; display: block; margin-top: 30px; background: var(--muted); }
    .btn-volver:hover { background: #4b5563; }
    .stepper-wrapper { margin: 40px 0 20px; }
    .stepper-container { display: flex; justify-content: space-between; position: relative; }
    .stepper-container::before { content: ''; position: absolute; top: 15px; left: 10%; width: 80%; height: 4px; background: var(--border); z-index: 1; }
    .stepper-progress { position: absolute; top: 15px; left: 10%; height: 4px; background: var(--primary); z-index: 2; transition: width 0.4s ease; width: 0%; }
    .step { position: relative; z-index: 3; text-align: center; width: 25%; }
    .step-circle { width: 34px; height: 34px; background: var(--panel); border: 4px solid var(--border); border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: transparent; transition: all 0.3s; }
    .step-label { font-size: 0.85rem; color: var(--muted); font-weight: 600; }
    .step.active .step-circle, .step.completed .step-circle { border-color: var(--primary); background: var(--primary); color: white; }
    .step.active .step-label { color: var(--primary); }
    .cartel-final { display: none; text-align: center; padding: 30px; border-radius: 12px; margin: 40px 0 20px; font-size: 1.8rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
    .cartel-resuelto { background: #dcfce7; color: #166534; border: 3px solid #22c55e; }
    .cartel-denegada { background: #fee2e2; color: #991b1b; border: 3px solid #ef4444; }
  </style>
</head>
<body>
  <main class="detalle-container">
    <section class="panel">
      <div class="header-detalle">
        <h2 style="margin: 0;">Folio: <span id="folioID">Cargando...</span></h2>
        <span id="badgeEstado" class="report-status">...</span>
      </div>

      <div class="form-row">
        <div class="dato-grupo">
          <span class="dato-label">Tipo de Incidencia / Categoría</span>
          <div class="dato-valor" id="txtTipo" style="text-transform: capitalize;">...</div>
        </div>
        <div class="dato-grupo">
          <span class="dato-label">Fecha de Creación</span>
          <div class="dato-valor" id="txtFechaLevantamiento">...</div>
        </div>
      </div>

      <div class="dato-grupo" id="contenedorResolucion">
        <span class="dato-label" style="color: #15803d;">Fecha de Resolución</span>
        <div class="dato-valor" id="txtFechaResolucion" style="background: #f0fdf4; border-color: #bbf7d0;">...</div>
      </div>

      <div class="dato-grupo">
        <span class="dato-label">Descripción y Ubicación del Problema</span>
        <div class="dato-valor" id="txtDescripcion">...</div>
      </div>

      <!-- MAPA DEL REPORTE -->
      <div class="dato-grupo">
        <span class="dato-label">Mapa de Ubicación del Incidente</span>
        <div id="mapaDetalle" style="height: 280px; width: 100%; border-radius: 8px; border: 1px solid var(--border); margin-top: 6px;"></div>
      </div>

      <div class="dato-grupo">
        <span class="dato-label">Evidencia Fotográfica</span>
        <img id="imgEvidencia" src="" alt="Evidencia" class="foto-evidencia" style="display: none;" />
      </div>

      <div class="stepper-wrapper" id="bloqueLineaTiempo">
        <div class="stepper-container">
          <div class="stepper-progress" id="barraProgreso"></div>
          <div class="step" id="step-0"><div class="step-circle"></div><div class="step-label">Reporte Recibido</div></div>
          <div class="step" id="step-1"><div class="step-circle"></div><div class="step-label">En revisión</div></div>
          <div class="step" id="step-2"><div class="step-circle"></div><div class="step-label">En proceso</div></div>
          <div class="step" id="step-3"><div class="step-circle"></div><div class="step-label">Resuelto</div></div>
        </div>
      </div>

      <div id="cartelResuelto" class="cartel-final cartel-resuelto">REPORTE RESUELTO</div>
      <div id="cartelDenegada" class="cartel-final cartel-denegada">REPORTE DENEGADO</div>

      <a href="Pagina.php" class="btn btn-volver">Volver al inicio</a>
    </section>
  </main>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    const reporteID = urlParams.get('id');

    function renderizarMapa(lat, lng) {
      const mapa = new maplibregl.Map({
        container: 'mapaDetalle',
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
          layers: [{ id: 'osm-tiles', type: 'raster', source: 'osm-tiles', minzoom: 10, maxzoom: 19 }]
        },
        center: [lng, lat],
        zoom: 16
      });

      mapa.addControl(new maplibregl.NavigationControl());

      new maplibregl.Marker({ color: '#e11d48' })
        .setLngLat([lng, lat])
        .addTo(mapa);
    }

    async function buscarUbicacionYRenderizar(textoUbicacion) {
      const centroVeracruz = [19.1738, -96.1348];
      try {
        let query = textoUbicacion.split('\n')[0].replace('Ubicación:', '').trim();
        if (!query.toLowerCase().includes('veracruz')) {
          query += ', Veracruz';
        }

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=mx&bounded=1&viewbox=-96.35,19.30,-96.00,19.00`;
        const res = await fetch(url);
        const resultados = await res.json();

        if (resultados && resultados.length > 0) {
          renderizarMapa(parseFloat(resultados[0].lat), parseFloat(resultados[0].lon));
        } else {
          renderizarMapa(centroVeracruz[0], centroVeracruz[1]);
        }
      } catch (err) {
        renderizarMapa(centroVeracruz[0], centroVeracruz[1]);
      }
    }

    document.addEventListener("DOMContentLoaded", async () => {
      if (!reporteID) {
        alert("No se especificó ningún reporte.");
        return;
      }

      try {
        const respuesta = await fetch(`../index.php/api/reportes?id=${reporteID}`);
        const datosNube = await respuesta.json();

        if (datosNube.error) {
          alert(datosNube.error);
          return;
        }

        document.getElementById('folioID').innerText = datosNube.folio || ("#" + datosNube.id);
        document.getElementById('badgeEstado').innerText = datosNube.estatus;
        document.getElementById('txtTipo').innerText = datosNube.categoria;
        document.getElementById('txtDescripcion').innerText = datosNube.descripcion || 'Sin descripción';
        document.getElementById('txtFechaLevantamiento').innerText = datosNube.fecha_creacion;

        // Renderizar el mapa del reporte
        if (datosNube.latitud && datosNube.longitud) {
          renderizarMapa(parseFloat(datosNube.latitud), parseFloat(datosNube.longitud));
        } else {
          buscarUbicacionYRenderizar(datosNube.descripcion || datosNube.titulo || 'Veracruz');
        }

        // Cargar evidencia fotográfica
        if (datosNube.foto_url) {
          const img = document.getElementById('imgEvidencia');
          img.src = datosNube.foto_url.startsWith('http') || datosNube.foto_url.startsWith('../')
            ? datosNube.foto_url
            : '../' + datosNube.foto_url;
          img.style.display = "block";
        }

        // Línea de tiempo / Estados
        const lineaTiempo = document.getElementById('bloqueLineaTiempo');
        const cartelResuelto = document.getElementById('cartelResuelto');
        const cartelDenegada = document.getElementById('cartelDenegada');
        const barraProgreso = document.getElementById('barraProgreso');

        const estatusLower = (datosNube.estatus || '').toLowerCase();

        if (estatusLower === 'denegada' || estatusLower === 'cancelado') {
          lineaTiempo.style.display = 'none';
          cartelDenegada.style.display = 'block';
          let badge = document.getElementById('badgeEstado');
          badge.style.background = "#fee2e2";
          badge.style.color = "#991b1b";
          return;
        }

        if (estatusLower === 'resuelto') {
          lineaTiempo.style.display = 'none';
          cartelResuelto.style.display = 'block';
          let badge = document.getElementById('badgeEstado');
          badge.style.background = "#dcfce7";
          badge.style.color = "#166534";
          return;
        }

        const estadosProceso = ["reporte recibido", "en revision", "en proceso", "resuelto"];
        const pasoActual = estadosProceso.indexOf(estatusLower);

        if (pasoActual >= 0) {
          const porcentajes = ["0%", "33%", "66%", "100%"];
          barraProgreso.style.width = porcentajes[pasoActual];

          for (let i = 0; i <= pasoActual; i++) {
            const circulo = document.getElementById(`step-${i}`);
            if (circulo) {
              if (i === pasoActual) {
                circulo.classList.add('active');
              } else {
                circulo.classList.add('completed');
              }
            }
          }
        }
      } catch (error) {
        console.error("Error al obtener los datos del reporte:", error);
      }
    });
  </script>
</body>
</html>