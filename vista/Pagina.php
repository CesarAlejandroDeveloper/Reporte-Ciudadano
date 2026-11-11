<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CiudadReporta - Obras Públicas</title>
  <!-- Enlace a tu hoja de estilos reutilizable -->
  <link rel="stylesheet" href="../CSS/styles.css" />
</head>
<body>

  <main class="layout-grid">
    
    <aside class="panel panel-left">
      <h2>Reportes Recientes</h2>
      <!-- Este div puede ser poblado dinámicamente con un GET a tu base de datos -->
      <div class="report-list" id="contenedorReportes">
        <div class="report-card">
          <h3>Bache profundo</h3>
          <span class="report-date">11 de Noviembre, 2026</span>
          <span class="report-status">Revisión</span>
        </div>
      </div>
    </aside>

    <section class="panel panel-right">
      <h2>Levantar un Reporte</h2>
      <span class="subtitle">Dirigido a: Dirección de Obras Públicas y Desarrollo Urbano</span>
      
      <!-- Agregamos un ID al formulario para controlarlo con JavaScript -->
      <form id="formularioReporte">
        
        <div class="form-row">
          <div class="form-group">
            <label for="tipo_incidencia">Tipo de incidencia</label>
            <!-- El atributo 'name' será la llave en el JSON/FormData para tu backend -->
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
            <label for="ubicacion">Ubicación exacta (Coordenadas o Dirección)</label>
            <input type="text" id="ubicacion" name="ubicacion" placeholder="Ej: Calle 5 esquina con Av. Principal" required>
          </div>
        </div>

        <div class="form-group">
          <label for="descripcion">Descripción del problema</label>
          <textarea id="descripcion" name="descripcion" placeholder="Describe brevemente el problema..."></textarea>
        </div>

        <div class="form-group">
          <label for="evidencia">Foto de evidencia</label>
          <!-- 'name' = evidencia. Al llevar archivo, el request debe ser multipart/form-data -->
          <input type="file" id="evidencia" name="evidencia" accept="image/png, image/jpeg" required>
        </div>

        <button type="submit" class="btn">Enviar reporte ciudadano</button>
      </form>
    </section>
  </main>

  <script>
    // Lógica para interceptar los datos y enviarlos al backend
    document.getElementById('formularioReporte').addEventListener('submit', async function(evento) {
      // 1. Evitar que la página se recargue al enviar
      evento.preventDefault(); 

      // 2. Extraer automáticamente todos los inputs según su atributo 'name'
      const formularioDatos = new FormData(this);

      // Si necesitas ver las variables extraídas en la consola antes de mandarlas:
      console.log("Tipo:", formularioDatos.get('tipo_incidencia'));
      console.log("Ubicación:", formularioDatos.get('ubicacion'));
      console.log("Descripción:", formularioDatos.get('descripcion'));
      console.log("Archivo:", formularioDatos.get('evidencia'));

      try {
        // 3. Enviar al backend (Ejemplo de endpoint /api/reportes)
        const respuesta = await fetch('/api/reportes', {
          method: 'POST',
          // Al usar FormData, el navegador configura automáticamente el header 'multipart/form-data'
          body: formularioDatos 
        });

        if (respuesta.ok) {
          alert("Reporte enviado correctamente a la base de datos.");
          this.reset(); // Limpiar el formulario
          // Aquí podrías llamar a una función que actualice la lista izquierda
        } else {
          alert("Error al enviar el reporte.");
        }
      } catch (error) {
        console.error("Error de conexión con el backend:", error);
      }
    });
  </script>
</body>
</html>