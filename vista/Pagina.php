<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CiudadReporta - Obras Públicas</title>
  <style>
    :root {
      /* Paleta institucional basada en veracruz.gob.mx */
      --bg: #f5f5f5; /* Gris muy claro para el fondo */
      --panel: #ffffff; /* Blanco puro para los paneles */
      --primary: #75203D; /* Guinda institucional */
      --primary-hover: #5A172E; /* Guinda oscuro para hover */
      --accent: #C9A15B; /* Dorado institucional */
      --text: #333333; /* Texto oscuro */
      --muted: #666666; /* Texto secundario */
      --border: #d1d5db; /* Bordes sutiles */
      
      /* Colores para la etiqueta de estado */
      --status-bg: #fdf3e1; /* Fondo dorado muy claro */
      --status-text: #8c6819; /* Texto dorado oscuro */
    }

    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      padding: 40px 20px;
    }

    /* Contenedor principal con Grid: 30% izquierda, 70% derecha */
    .layout-grid {
      display: grid;
      grid-template-columns: 3fr 7fr;
      gap: 24px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .panel {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      border-top: 4px solid var(--primary); /* Acento guinda en el borde superior */
    }

    h2 {
      color: var(--primary);
      margin-bottom: 20px;
      font-size: 1.5rem;
    }
    
    h3 {
      font-size: 1.1rem;
      color: var(--text);
      margin-bottom: 8px;
    }

    .subtitle {
      color: var(--muted);
      font-size: 0.9rem;
      margin-bottom: 24px;
      display: block;
    }

    /* Estilos de la columna izquierda (Reportes) */
    .report-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .report-card {
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 16px;
      background: #fafafa;
      border-left: 4px solid var(--accent); /* Acento dorado en las tarjetas */
    }

    .report-date {
      font-size: 0.85rem;
      color: var(--muted);
      font-weight: 600;
      display: block;
      margin-bottom: 8px;
    }

    .report-status {
      display: inline-block;
      background: var(--status-bg);
      color: var(--status-text);
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: bold;
      text-transform: uppercase;
    }

    /* Estilos del formulario (Columna derecha) */
    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 0.95rem;
      color: var(--text);
    }

    input[type="text"],
    input[type="file"],
    select,
    textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--border);
      border-radius: 6px;
      font-family: inherit;
      background: #fff;
    }

    textarea {
      resize: vertical;
      min-height: 100px;
    }

    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(201, 161, 91, 0.2); /* Sombra dorada al enfocar */
    }

    /* Fila para agrupar campos */
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .btn {
      display: inline-block;
      width: 100%;
      padding: 14px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn:hover {
      background: var(--primary-hover);
    }

    /* Responsive: Apilar columnas en pantallas menores a 768px */
    @media (max-width: 768px) {
      .layout-grid {
        grid-template-columns: 1fr;
      }
      
      .panel-left {
        order: 2;
      }
      .panel-right {
        order: 1;
      }
    }
  </style>
</head>
<body>

  <main class="layout-grid">
    
    <!-- LADO IZQUIERDO: 30% - Historial de Reportes -->
    <aside class="panel panel-left">
      <h2>Reportes Recientes</h2>
      <div class="report-list">
        
        <div class="report-card">
          <h3>Bache profundo</h3>
          <span class="report-date">11 de Noviembre, 2026</span>
          <span class="report-status">Revisión</span>
        </div>

        <div class="report-card">
          <h3>Luminaria apagada</h3>
          <span class="report-date">10 de Noviembre, 2026</span>
          <span class="report-status">Revisión</span>
        </div>

      </div>
    </aside>

    <!-- LADO DERECHO: 70% - Formulario -->
    <section class="panel panel-right">
      <h2>Levantar un Reporte</h2>
      <span class="subtitle">Dirigido a: Dirección de Obras Públicas y Desarrollo Urbano</span>
      
      <form action="#" method="POST" enctype="multipart/form-data">
        
        <div class="form-row">
          <div class="form-group">
            <label for="tipo_incidencia">Tipo de incidencia</label>
            <select id="tipo_incidencia" name="tipo_incidencia" required>
              <option value="">Selecciona una opción...</option>
              <option value="bache">Bache en vialidad</option>
              <option value="luminaria">Luminaria fundida / farol</option>
              <option value="accidente">Daño por accidente vial</option>
              <option value="fuga">Fuga de agua</option>
              <option value="otro">Otro</option>
            </select>
          </div>

          <div class="form-group">
            <label for="ubicacion">Ubicación exacta</label>
            <input type="text" id="ubicacion" name="ubicacion" placeholder="Ej: Calle 5 esquina con Av. Principal" required>
          </div>
        </div>

        <div class="form-group">
          <label for="descripcion">Descripción del problema</label>
          <textarea id="descripcion" name="descripcion" placeholder="Describe brevemente el problema..."></textarea>
        </div>

        <div class="form-group">
          <label for="evidencia">Foto de evidencia</label>
          <input type="file" id="evidencia" name="evidencia" accept="image/png, image/jpeg" required>
        </div>

        <button type="submit" class="btn">Enviar reporte ciudadano</button>
      </form>
    </section>

  </main>

</body>
</html>