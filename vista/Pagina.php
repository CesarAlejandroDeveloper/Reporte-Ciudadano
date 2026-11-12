<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>CiudadReporta - Obras Públicas</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <!-- Librería de mapa vectorial/raster -->
  <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
  <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
  <script src="https://unpkg.com/pmtiles@2.13.0/dist/index.js"></script>

  <style>
    /* Estilos optimizados para móviles */
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

    /* Botones táctiles duales para fotos */
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
    .btn-camara-directa {
      background: #2563eb; /* Azul para cámara */
    }
    .btn-galeria-directa {
      background: #4b5563; /* Gris oscuro para galería */
    }

    .preview-contenedor {
      display: none;
      margin-top: 12px;
      padding: 10px;
      background: #f8fafc;
      border-radius: 8px;
      border: 1px solid var(--border, #cbd5e1);
      text-align: center;
    }
    .preview-img {
      max-width: 100%;
      max-height: 220px;
      border-radius: 6px;
      object-fit: cover;
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
              <input type="text" id="ubicacion" name="ubicacion" class="input-mobile" placeholder="Ej: Av. Díaz Mirón, Veracruz" required style="flex: 1;">
              <button type="button" id="btnToggleMapa" title="Seleccionar en el mapa" class="btn" style="width: auto; padding: 0 16px; display: flex; align-items: center; justify-content: center; gap: 4px; white-space: nowrap;">
                📍 <span>Mapa</span>
              </button>
            </div>
          </div>
        </div>

        <div id="contenedorMapa" style="display: none; margin-bottom: 16px;">
          <label style="font-size: 0.85rem; color: var(--muted, #666); margin-bottom: 4px; display: block;">Toca el mapa para mover el pin al punto exacto:</label>
          <div id="mapa" style="height: 260px; width: 100%; border-radius: 8px; border: 1px solid var(--border, #ccc);"></div>
        </div>

        <div class="form-group">
          <label for="descripcion">Descripción del problema</label>
          <textarea id="descripcion" name="descripcion" class="input-mobile" placeholder="Describe brevemente el problema..."></textarea>
        </div>

        <!-- SECCIÓN DE FOTOGRAFÍA CON DOBLE OPCIÓN EN MÓVIL -->
        <div class="form-group">
          <label>Fotografía de Evidencia</label>

          <!-- Input 1: Cámara directa (Abre la app de cámara) -->
          <input type="file" id="inputCamara" accept="image/*" capture="environment" style="display: none;">
          
          <!-- Input 2: Galería de fotos (Abre las fotos guardadas en el cel) -->
          <input type="file" id="inputGaleria" accept="image/*" style="display: none;">

          <!-- Botones de selección -->
          <div class="grid-foto-opciones">
            <button type="button" id="btnAbrirCamara" class="btn-media-opcion btn-camara-directa">
              📷 <span>Tomar Foto</span>
            </button>
            <button type="button" id="btnAbrirGaleria" class="btn-media-opcion btn-galeria-directa">
              🖼️ <span>Subir Galería</span>
            </button>
          </div>

          <!-- Previsualización y botón de eliminación -->
          <div class="preview-contenedor" id="contenedorPreview">
            <img id="imgPreview" src="" alt="Vista previa" class="preview-img" />
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
              <small id="nombreArchivo" style="color: var(--muted); font-size: 0.8rem;"></small>
              <button type="button" id="btnQuitarFoto" style="background: none; border: none; color: #dc2626; font-weight: 700; cursor: pointer; font-size: 0.85rem;">✕ Quitar foto</button>
            </div>
          </div>
        </div>

        <button type="submit" class="btn" style="padding: 16px; font-size: 1.05rem; font-weight: 700; margin-top: 10px;">Enviar reporte ciudadano</button>
      </form>
    </section>
  </main>

  <script>
    const API_URL = '../index.php/api/reportes';

    // --- 1. MANEJO INTELIGENTE DE CÁMARA VS GALERÍA ---
    let archivoSeleccionado = null;

    const inputCamara = document.getElementById('inputCamara');
    const inputGaleria = document.getElementById('inputGaleria');
    const btnAbrirCamara = document.getElementById('btnAbrirCamara');
    const btnAbrirGaleria = document.getElementById('btnAbrirGaleria');
    const contenedorPreview = document.getElementById('contenedorPreview');
    const imgPreview = document.getElementById('imgPreview');
    const nombreArchivo = document.getElementById('nombreArchivo');
    const btnQuitarFoto = document.getElementById('btnQuitarFoto');

    btnAbrirCamara.addEventListener('click', () => inputCamara.click());
    btnAbrirGaleria.addEventListener('click', () => inputGaleria.click());

    function cargarPrevisualizacion(archivo) {
      if (!archivo) return;
      archivoSeleccionado = archivo;
      
      const lector = new FileReader();
      lector.onload = function(e) {
        imgPreview.src = e.target.result;
        contenedorPreview.style.display = 'block';
        nombreArchivo.innerText = archivo.name;
      };
      lector.readAsDataURL(archivo);
    }

    inputCamara.addEventListener('change', function() {
      if (this.files[0]) cargarPrevisualizacion(this.files[0]);
    });

    inputGaleria.addEventListener('change', function() {
      if (this.files[0]) cargarPrevisualizacion(this.files[0]);
    });

    btnQuitarFoto.addEventListener('click', () => {
      archivoSeleccionado = null;
      inputCamara.value = '';
      inputGaleria.value = '';
      contenedorPreview.style.display = 'none';
      imgPreview.src = '';
    });

    // --- 2. CONFIGURACIÓN DEL MAPA ---
    const centroInicial = [-96.1348, 19.1738];
    const limitesVeracruz = [
      [-96.3500, 19.0000],
      [-96.0000, 19.3000]
    ];

    let map = null;
    let marker = null;

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

      function escribirCoordenadas(lngLat) {
        const lat = lngLat.lat.toFixed(6);
        const lng = lngLat.lng.toFixed(6);
        document.getElementById('ubicacion').value = `${lat}, ${lng}`;
      }

      marker.on('dragend', () => escribirCoordenadas(marker.getLngLat()));
      map.on('click', (e) => {
        marker.setLngLat(e.lngLat);
        escribirCoordenadas(e.lngLat);
      });
    }

    // --- 3. BÚSQUEDA AL PRESIONAR ENTER EN MÓVIL ---
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
            const coords = [parseFloat(resultados[0].lon), parseFloat(resultados[0].lat)];
            map.setCenter(coords);
            map.setZoom(16);
            marker.setLngLat(coords);
          }
        } catch (error) {
          console.error("Error al buscar la ubicación:", error);
        }
      }
    });

    // --- 4. BOTÓN DESPLEGAR MAPA ---
    document.getElementById('btnToggleMapa').addEventListener('click', () => {
      if (contenedorMapa.style.display === 'none') {
        contenedorMapa.style.display = 'block';
        inicializarMapa();
        setTimeout(() => map.resize(), 100);
      } else {
        contenedorMapa.style.display = 'none';
      }
    });

    // --- 5. ENVIAR FORMULARIO CON LA FOTO SELECCIONADA ---
    document.getElementById('formularioReporte').addEventListener('submit', async function(evento) {
      evento.preventDefault();

      if (!archivoSeleccionado) {
        alert("Por favor toma una fotografía o selecciona una imagen de tu galería como evidencia.");
        return;
      }
      
      const formularioDatos = new FormData(this);
      // Adjuntar explícitamente el archivo de la cámara o galería
      formularioDatos.append('evidencia', archivoSeleccionado);

      try {
        const respuesta = await fetch(API_URL, {
          method: 'POST',
          body: formularioDatos 
        });

        const resultado = await respuesta.json();

        if (respuesta.ok) {
          alert("Reporte guardado con éxito. Folio: " + (resultado.folio || resultado.id));
          this.reset();
          btnQuitarFoto.click();
          contenedorMapa.style.display = 'none';
          cargarReportesBD();
        } else {
          alert("Error al guardar: " + (resultado.error || "Error desconocido"));
        }
      } catch (error) {
        console.error("Error de conexión:", error);
        alert("No se pudo conectar con el servidor.");
      }
    });

    // --- 6. CARGAR REPORTES RECIENTES ---
    async function cargarReportesBD() {
      const contenedorReportes = document.getElementById('contenedorReportes');
      contenedorReportes.innerHTML = '<p style="color: var(--muted); font-size: 0.9rem;">Cargando reportes...</p>';
      
      try {
        const respuesta = await fetch(API_URL);
        const reportesDB = await respuesta.json();
        
        contenedorReportes.innerHTML = '';

        if (!Array.isArray(reportesDB) || reportesDB.length === 0) {
          contenedorReportes.innerHTML = '<p style="color: var(--muted); font-size: 0.9rem;">Aún no hay reportes registrados.</p>';
          return;
        }

        reportesDB.forEach(reporte => {
          const tituloMostrar = reporte.titulo || reporte.categoria || 'Reporte Ciudadano';
          const tarjeta = `
            <a href="Reporte.php?id=${reporte.id}" style="text-decoration: none; color: inherit;">
              <div class="report-card" style="cursor: pointer;">
                <h3 style="text-transform: capitalize;">${tituloMostrar}</h3>
                <span class="report-date">${reporte.fecha_creacion}</span>
                <span class="report-status">${reporte.estatus}</span>
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