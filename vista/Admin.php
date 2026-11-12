<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Panel Administrador - CiudadReporta</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
  <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet" />
  <style>
    .admin-wrapper {
      max-width: 1250px;
      margin: 0 auto;
      padding: 20px;
    }
    .admin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 24px;
      border-bottom: 2px solid var(--border, #e5e7eb);
      padding-bottom: 16px;
    }
    .admin-header h1 {
      margin: 0;
      color: var(--primary, #691c32);
      font-size: 1.6rem;
    }
    .user-info-bar {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .btn-logout {
      background: #dc2626;
      color: #fff;
      border: none;
      padding: 8px 14px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 14px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: var(--panel, #fff);
      border: 1px solid var(--border, #e5e7eb);
      border-top: 4px solid var(--primary, #691c32);
      border-radius: 8px;
      padding: 14px 16px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .stat-card span {
      display: block;
      font-size: 0.85rem;
      color: var(--muted, #6b7280);
      font-weight: 600;
    }
    .stat-card strong {
      font-size: 1.6rem;
      color: var(--text, #111827);
    }
    .toolbar {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 18px;
    }
    .filter-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .filter-btn {
      padding: 8px 14px;
      border-radius: 20px;
      border: 1px solid var(--border, #d1d5db);
      background: #fff;
      color: var(--text, #374151);
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .filter-btn.active, .filter-btn:hover {
      background: var(--primary, #691c32);
      color: #fff;
      border-color: var(--primary, #691c32);
    }
    .search-input {
      padding: 9px 14px;
      border-radius: 6px;
      border: 1px solid var(--border, #d1d5db);
      min-width: 260px;
      font-size: 0.9rem;
    }
    .table-responsive {
      overflow-x: auto;
      background: var(--panel, #fff);
      border-radius: 8px;
      border: 1px solid var(--border, #e5e7eb);
    }
    table.admin-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.92rem;
    }
    .admin-table th {
      background: #f9fafb;
      padding: 13px 16px;
      font-weight: 700;
      color: var(--primary, #691c32);
      border-bottom: 2px solid var(--border, #e5e7eb);
      white-space: nowrap;
    }
    .admin-table td {
      padding: 13px 16px;
      border-bottom: 1px solid var(--border, #e5e7eb);
      vertical-align: middle;
    }
    .admin-table tr:hover {
      background: #fdf2f4;
    }
    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
    }
    .badge-recibido { background: #e0f2fe; color: #0369a1; }
    .badge-revision { background: #fef3c7; color: #92400e; }
    .badge-proceso  { background: #ede9fe; color: #5b21b6; }
    .badge-resuelto { background: #dcfce7; color: #166534; }
    .badge-denegada { background: #fee2e2; color: #991b1b; }

    .btn-accion {
      padding: 6px 12px;
      border-radius: 6px;
      border: none;
      background: var(--primary, #691c32);
      color: #fff;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
    }
    .btn-accion:hover { opacity: 0.9; }

    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .modal-overlay.open {
      display: flex;
    }
    .modal-card {
      background: #fff;
      width: 100%;
      max-width: 780px;
      max-height: 92vh;
      overflow-y: auto;
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 20px 35px rgba(0,0,0,0.25);
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border, #e5e7eb);
      padding-bottom: 12px;
      margin-bottom: 18px;
    }
    .btn-cerrar-modal {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--muted, #6b7280);
    }
    .modal-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 16px;
    }
    @media (max-width: 680px) {
      .modal-grid { grid-template-columns: 1fr; }
    }
    .info-box {
      background: #f9fafb;
      padding: 10px 12px;
      border-radius: 6px;
      border: 1px solid var(--border, #e5e7eb);
      font-size: 0.92rem;
      white-space: pre-line;
    }
    .decision-bar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
      gap: 10px;
      margin-top: 20px;
      padding-top: 16px;
      border-top: 1px solid var(--border, #e5e7eb);
    }
    .btn-status {
      padding: 11px 12px;
      border: none;
      border-radius: 8px;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      color: #fff;
      transition: transform 0.1s, opacity 0.2s;
    }
    .btn-status:hover { opacity: 0.92; transform: translateY(-1px); }
    .st-revision { background: #d97706; }
    .st-proceso  { background: #7c3aed; }
    .st-resuelto { background: #16a34a; }
    .st-baja     { background: #dc2626; }
  </style>
</head>
<body>
  <main class="admin-wrapper">
    <header class="admin-header">
      <div>
        <h1>Panel de Administración - Obras Públicas</h1>
        <span style="color: var(--muted, #6b7280); font-size: 0.9rem;">
          Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></strong>
        </span>
      </div>
      <div class="user-info-bar">
        <a href="Pagina.php" class="btn" style="width: auto; padding: 8px 14px; text-decoration: none;">Vista Ciudadana</a>
        <button type="button" class="btn-logout" id="btnCerrarSesion">Cerrar Sesión</button>
      </div>
    </header>

    <section class="stats-grid">
      <div class="stat-card">
        <span>Total Reportes</span>
        <strong id="stat-total">0</strong>
      </div>
      <div class="stat-card" style="border-top-color: #0284c7;">
        <span>Recibidos</span>
        <strong id="stat-recibido">0</strong>
      </div>
      <div class="stat-card" style="border-top-color: #d97706;">
        <span>En Revisión</span>
        <strong id="stat-revision">0</strong>
      </div>
      <div class="stat-card" style="border-top-color: #7c3aed;">
        <span>En Proceso</span>
        <strong id="stat-proceso">0</strong>
      </div>
      <div class="stat-card" style="border-top-color: #16a34a;">
        <span>Resueltos</span>
        <strong id="stat-resuelto">0</strong>
      </div>
      <div class="stat-card" style="border-top-color: #dc2626;">
        <span>Dados de Baja</span>
        <strong id="stat-denegada">0</strong>
      </div>
    </section>

    <div class="toolbar">
      <div class="filter-group" id="filtrosEstatus">
        <button class="filter-btn active" data-filtro="todos">Todos</button>
        <button class="filter-btn" data-filtro="reporte recibido">Recibidos</button>
        <button class="filter-btn" data-filtro="en revision">En revisión</button>
        <button class="filter-btn" data-filtro="en proceso">En proceso</button>
        <button class="filter-btn" data-filtro="resuelto">Resueltos</button>
        <button class="filter-btn" data-filtro="denegada">Dados de baja</button>
      </div>

      <input type="text" id="buscadorAdmin" class="search-input" placeholder="Buscar por folio, título o categoría..." />
    </div>

    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Folio</th>
            <th>Categoría</th>
            <th>Título / Ubicación</th>
            <th>Prioridad</th>
            <th>Fecha</th>
            <th>Estatus</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tablaReportesBody">
          <tr>
            <td colspan="7" style="text-align: center; padding: 24px; color: var(--muted);">Cargando reportes...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>

  <div class="modal-overlay" id="modalGestion">
    <div class="modal-card">
      <div class="modal-header">
        <h2 style="margin: 0; color: var(--primary);">Gestionar Reporte: <span id="modalFolio"></span></h2>
        <button type="button" class="btn-cerrar-modal" id="btnCerrarModal">&times;</button>
      </div>

      <div class="modal-grid">
        <div>
          <label style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">Categoría e Incidencia</label>
          <div class="info-box" id="modalCategoria" style="text-transform: capitalize; margin-bottom: 12px;"></div>

          <label style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">Descripción y Ubicación</label>
          <div class="info-box" id="modalDescripcion" style="margin-bottom: 12px;"></div>

          <div style="display: flex; gap: 12px; align-items: center;">
            <div style="flex: 1;">
              <label for="modalPrioridad" style="font-weight: 600; font-size: 0.85rem; color: var(--primary); display: block; margin-bottom: 4px;">Prioridad</label>
              <select id="modalPrioridad" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border);">
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>
            <div>
              <label style="font-weight: 600; font-size: 0.85rem; color: var(--primary); display: block; margin-bottom: 4px;">Estatus Actual</label>
              <span id="modalBadgeActual" class="badge"></span>
            </div>
          </div>
        </div>

        <div>
          <label style="font-weight: 600; font-size: 0.85rem; color: var(--primary); display: block; margin-bottom: 4px;">Ubicación en Mapa</label>
          <div id="modalMapa" style="height: 170px; width: 100%; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 12px;"></div>

          <label style="font-weight: 600; font-size: 0.85rem; color: var(--primary); display: block; margin-bottom: 4px;">Evidencia Fotográfica</label>
          <div id="modalContenedorFoto">
            <img id="modalFoto" src="" alt="Evidencia" style="max-height: 150px; width: 100%; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); display: none;" />
            <span id="modalSinFoto" style="font-size: 0.85rem; color: var(--muted);">Sin fotografía adjunta</span>
          </div>
        </div>
      </div>

      <div>
        <span style="font-weight: 700; font-size: 0.9rem; color: var(--text);">Dictaminar / Cambiar Estatus del Reporte:</span>
        <div class="decision-bar">
          <button type="button" class="btn-status st-revision" onclick="cambiarEstatus('en revision')">En Revisión</button>
          <button type="button" class="btn-status st-proceso" onclick="cambiarEstatus('en proceso')">En Proceso</button>
          <button type="button" class="btn-status st-resuelto" onclick="cambiarEstatus('resuelto')">Marcar Resuelto</button>
          <button type="button" class="btn-status st-baja" onclick="darDeBajaReporte()">Dar de Baja</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const API_LISTAR = '../index.php/api/reportes';
    const API_ESTATUS = '../index.php/api/reportes/estatus';
    const API_LOGOUT = '../index.php/api/logout';

    let todosLosReportes = [];
    let filtroActual = 'todos';
    let reporteSeleccionado = null;
    let modalMap = null;
    let modalMarker = null;

    // CERRAR SESIÓN
    document.getElementById('btnCerrarSesion').addEventListener('click', async () => {
      await fetch(API_LOGOUT);
      window.location.href = 'Login.php';
    });

    // CARGAR REPORTES
    async function cargarReportesAdmin() {
      try {
        const res = await fetch(API_LISTAR);
        const data = await res.json();
        todosLosReportes = Array.isArray(data) ? data : [];
        actualizarContadores();
        renderizarTabla();
      } catch (err) {
        console.error("Error cargando reportes:", err);
        document.getElementById('tablaReportesBody').innerHTML =
          '<tr><td colspan="7" style="text-align:center; color:#dc2626; padding:20px;">Error al conectar con la base de datos.</td></tr>';
      }
    }

    function actualizarContadores() {
      document.getElementById('stat-total').innerText = todosLosReportes.length;
      document.getElementById('stat-recibido').innerText = todosLosReportes.filter(r => (r.estatus || '').toLowerCase() === 'reporte recibido').length;
      document.getElementById('stat-revision').innerText = todosLosReportes.filter(r => (r.estatus || '').toLowerCase() === 'en revision').length;
      document.getElementById('stat-proceso').innerText = todosLosReportes.filter(r => (r.estatus || '').toLowerCase() === 'en proceso').length;
      document.getElementById('stat-resuelto').innerText = todosLosReportes.filter(r => (r.estatus || '').toLowerCase() === 'resuelto').length;
      document.getElementById('stat-denegada').innerText = todosLosReportes.filter(r => ['denegada', 'cancelado'].includes((r.estatus || '').toLowerCase())).length;
    }

    function obtenerClaseBadge(estatus) {
      const st = (estatus || '').toLowerCase();
      if (st === 'en revision') return 'badge-revision';
      if (st === 'en proceso') return 'badge-proceso';
      if (st === 'resuelto') return 'badge-resuelto';
      if (st === 'denegada' || st === 'cancelado') return 'badge-denegada';
      return 'badge-recibido';
    }

    function formatearTextoEstatus(estatus) {
      const st = (estatus || '').toLowerCase();
      if (st === 'denegada' || st === 'cancelado') return 'Dado de baja';
      return estatus;
    }

    function renderizarTabla() {
      const tbody = document.getElementById('tablaReportesBody');
      const textoBusqueda = document.getElementById('buscadorAdmin').value.toLowerCase().trim();

      const filtrados = todosLosReportes.filter(rep => {
        const st = (rep.estatus || '').toLowerCase();
        const coincideFiltro =
          filtroActual === 'todos' ||
          (filtroActual === 'denegada' && (st === 'denegada' || st === 'cancelado')) ||
          st === filtroActual;

        const contenido = `${rep.folio || ''} ${rep.titulo || ''} ${rep.categoria || ''} ${rep.descripcion || ''}`.toLowerCase();
        const coincideTexto = !textoBusqueda || contenido.includes(textoBusqueda);

        return coincideFiltro && coincideTexto;
      });

      if (filtrados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:var(--muted);">No hay reportes que coincidan con el filtro.</td></tr>';
        return;
      }

      tbody.innerHTML = filtrados.map(rep => `
        <tr>
          <td><strong>${rep.folio || '#' + rep.id}</strong></td>
          <td style="text-transform: capitalize;">${rep.categoria || '-'}</td>
          <td>${rep.titulo || 'Sin título'}</td>
          <td style="text-transform: capitalize;">${rep.prioridad || 'media'}</td>
          <td style="white-space: nowrap;">${rep.fecha_creacion || ''}</td>
          <td><span class="badge ${obtenerClaseBadge(rep.estatus)}">${formatearTextoEstatus(rep.estatus)}</span></td>
          <td>
            <button type="button" class="btn-accion" onclick="abrirModalGestion(${rep.id})">Gestionar</button>
          </td>
        </tr>
      `).join('');
    }

    function abrirModalGestion(id) {
      reporteSeleccionado = todosLosReportes.find(r => Number(r.id) === Number(id));
      if (!reporteSeleccionado) return;

      document.getElementById('modalFolio').innerText = reporteSeleccionado.folio || ('#' + reporteSeleccionado.id);
      document.getElementById('modalCategoria').innerText = reporteSeleccionado.categoria || 'General';
      document.getElementById('modalDescripcion').innerText = reporteSeleccionado.descripcion || 'Sin descripción';
      document.getElementById('modalPrioridad').value = (reporteSeleccionado.prioridad || 'media').toLowerCase();

      const badge = document.getElementById('modalBadgeActual');
      badge.className = 'badge ' + obtenerClaseBadge(reporteSeleccionado.estatus);
      badge.innerText = formatearTextoEstatus(reporteSeleccionado.estatus);

      const img = document.getElementById('modalFoto');
      const sinFoto = document.getElementById('modalSinFoto');
      if (reporteSeleccionado.foto_url) {
        img.src = reporteSeleccionado.foto_url.startsWith('http') || reporteSeleccionado.foto_url.startsWith('../')
          ? reporteSeleccionado.foto_url
          : '../' + reporteSeleccionado.foto_url;
        img.style.display = 'block';
        sinFoto.style.display = 'none';
      } else {
        img.style.display = 'none';
        sinFoto.style.display = 'block';
      }

      document.getElementById('modalGestion').classList.add('open');

      const centroVeracruz = [-96.1348, 19.1738];
      const tieneCoords = reporteSeleccionado.latitud && reporteSeleccionado.longitud;
      const coords = tieneCoords
        ? [parseFloat(reporteSeleccionado.longitud), parseFloat(reporteSeleccionado.latitud)]
        : centroVeracruz;

      setTimeout(() => {
        if (!modalMap) {
          modalMap = new maplibregl.Map({
            container: 'modalMapa',
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
          modalMarker = new maplibregl.Marker({ color: '#e11d48' }).setLngLat(coords).addTo(modalMap);
        } else {
          modalMap.resize();
          modalMap.setCenter(coords);
          modalMap.setZoom(tieneCoords ? 16 : 13);
          modalMarker.setLngLat(coords);
        }
      }, 120);
    }

    document.getElementById('btnCerrarModal').addEventListener('click', () => {
      document.getElementById('modalGestion').classList.remove('open');
    });

    async function cambiarEstatus(nuevoEstatus) {
      if (!reporteSeleccionado) return;
      const prioridadSeleccionada = document.getElementById('modalPrioridad').value;

      try {
        const res = await fetch(API_ESTATUS, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: reporteSeleccionado.id,
            estatus: nuevoEstatus,
            prioridad: prioridadSeleccionada
          })
        });

        const data = await res.json();

        if (res.ok) {
          alert(`El reporte ${reporteSeleccionado.folio || '#' + reporteSeleccionado.id} ahora está: ${formatearTextoEstatus(nuevoEstatus).toUpperCase()}`);
          document.getElementById('modalGestion').classList.remove('open');
          await cargarReportesAdmin();
        } else {
          alert("Error: " + (data.error || "No se pudo actualizar"));
        }
      } catch (err) {
        console.error("Error de red:", err);
        alert("No se pudo conectar con el servidor.");
      }
    }

    function darDeBajaReporte() {
      if (!reporteSeleccionado) return;
      const confirmar = confirm(`¿Seguro que deseas dar de baja el reporte ${reporteSeleccionado.folio || '#' + reporteSeleccionado.id}? Aparecerá como denegado para el ciudadano.`);
      if (confirmar) {
        cambiarEstatus('denegada');
      }
    }

    document.querySelectorAll('#filtrosEstatus .filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('#filtrosEstatus .filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filtroActual = btn.dataset.filtro;
        renderizarTabla();
      });
    });

    document.getElementById('buscadorAdmin').addEventListener('input', renderizarTabla);

    document.addEventListener('DOMContentLoaded', cargarReportesAdmin);
  </script>
</body>
</html>