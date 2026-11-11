<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Detalle de Reporte - CiudadReporta</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <style>
    /* Estilos extra específicos para la vista de detalle */
    .detalle-container {
      max-width: 800px;
      margin: 0 auto;
    }
    .header-detalle {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      border-bottom: 1px solid var(--border);
      padding-bottom: 16px;
    }
    .dato-grupo {
      margin-bottom: 20px;
    }
    .dato-label {
      font-weight: 600;
      color: var(--primary);
      font-size: 0.95rem;
      margin-bottom: 4px;
      display: block;
    }
    .dato-valor {
      font-size: 1.05rem;
      color: var(--text);
      background: #fafafa;
      padding: 12px;
      border-radius: 6px;
      border: 1px solid var(--border);
    }
    .foto-evidencia {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      border: 1px solid var(--border);
      margin-top: 10px;
    }
    /* Ocultar elementos por defecto */
    #contenedorResolucion {
      display: none; 
    }
    .btn-volver {
      text-align: center;
      display: block;
      margin-top: 30px;
      background: var(--muted);
    }
    .btn-volver:hover {
      background: #4b5563;
    }
  </style>
</head>
<body>

  <main class="detalle-container">
    <section class="panel">
      
      <div class="header-detalle">
        <h2 style="margin: 0;">Folio del Reporte: <span id="folioID">Cargando...</span></h2>
        <span id="badgeEstado" class="report-status">...</span>
      </div>

      <div class="form-row">
        <div class="dato-grupo">
          <span class="dato-label">Tipo de Incidencia</span>
          <div class="dato-valor" id="txtTipo">...</div>
        </div>
        <div class="dato-grupo">
          <span class="dato-label">Ubicación Exacta</span>
          <div class="dato-valor" id="txtUbicacion">...</div>
        </div>
      </div>

      <div class="form-row">
        <div class="dato-grupo">
          <span class="dato-label">Fecha de Levantamiento</span>
          <div class="dato-valor" id="txtFechaLevantamiento">...</div>
        </div>
        
        <!-- Este contenedor se mostrará SOLO si está resuelto -->
        <div class="dato-grupo" id="contenedorResolucion">
          <span class="dato-label" style="color: #15803d;">Fecha de Resolución</span>
          <div class="dato-valor" id="txtFechaResolucion" style="background: #f0fdf4; border-color: #bbf7d0;">...</div>
        </div>
      </div>

      <div class="dato-grupo">
        <span class="dato-label">Descripción del Problema</span>
        <div class="dato-valor" id="txtDescripcion">...</div>
      </div>

      <div class="dato-grupo">
        <span class="dato-label">Evidencia Fotográfica</span>
        <!-- Se inserta la URL de la nube aquí -->
        <img id="imgEvidencia" src="" alt="Evidencia del reporte" class="foto-evidencia" style="display: none;" />
      </div>

      <a href="Pagina.php" class="btn btn-volver">Volver al inicio</a>

    </section>
  </main>

  <script>
    // 1. Obtener el ID del reporte desde la URL (Ejemplo: Detalle.php?id=5)
    const urlParams = new URLSearchParams(window.location.search);
    const reporteID = urlParams.get('id');

    document.addEventListener("DOMContentLoaded", async () => {
      if (!reporteID) {
        alert("No se especificó ningún reporte.");
        return;
      }

      document.getElementById('folioID').innerText = "#" + reporteID;

      try {
        // 2. Conectar al backend (Nube/MySQL) para pedir los datos de este ID
        // Aquí ajustas la ruta a la API de tu nube/backend
        const respuesta = await fetch(`../controlador/ReporteController.php?accion=ver&id=${reporteID}`);
        
        // Simulación de los datos que te devolvería tu nube
        // (Borra esta simulación y usa `await respuesta.json()` cuando tu backend esté listo)
        const datosNube = {
          tipo_incidencia: "Bache profundo",
          ubicacion: "Calle 5 esquina con Av. Principal",
          descripcion: "Un bache muy grande que daña las llantas de los carros.",
          estado: "Resuelto", // Prueba cambiando a "Revisión"
          fecha_levantamiento: "2026-11-11 17:30:00", // La que se guardó en automático
          fecha_resolucion: "2026-11-15 10:00:00",
          ruta_foto: "https://via.placeholder.com/600x300?text=Foto+del+Bache" 
        };

        // 3. Pintar las variables en el HTML
        document.getElementById('badgeEstado').innerText = datosNube.estado;
        document.getElementById('txtTipo').innerText = datosNube.tipo_incidencia;
        document.getElementById('txtUbicacion').innerText = datosNube.ubicacion;
        document.getElementById('txtDescripcion').innerText = datosNube.descripcion;
        document.getElementById('txtFechaLevantamiento').innerText = datosNube.fecha_levantamiento;

        // Mostrar imagen si existe en la nube
        if (datosNube.ruta_foto) {
          const img = document.getElementById('imgEvidencia');
          img.src = datosNube.ruta_foto;
          img.style.display = "block";
        }

        // 4. Lógica Condicional: Fecha de Resolución
        if (datosNube.estado === 'Resuelto') {
          document.getElementById('contenedorResolucion').style.display = "block";
          document.getElementById('txtFechaResolucion').innerText = datosNube.fecha_resolucion;
          
          // Cambiar el color del badge a verde para que resalte
          const badge = document.getElementById('badgeEstado');
          badge.style.background = "#dcfce7";
          badge.style.color = "#166534";
        }

      } catch (error) {
        console.error("Error al obtener datos de la nube:", error);
      }
    });
  </script>
</body>
</html>