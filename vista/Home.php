<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CiudadReporta - Gobierno del Estado de Veracruz</title>
  
  <link rel="stylesheet" href="../CSS/styles.css">
  
  <style>
    /* --- ESTILOS DEL HEADER INSTITUCIONAL --- */
    .header-oficial {
      background-color: #ffffff;
      padding: 15px 5%;
      display: flex;
      justify-content: center; /* Centramos los logos al no haber elementos a la derecha */
      align-items: center;
      flex-wrap: wrap; 
    }
    
    .logos-contenedor {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .logo-img {
      height: 60px; 
      object-fit: contain;
    }

    /* --- LÍNEA DE COLORES OFICIAL --- */
    .linea-colores {
      height: 6px;
      width: 100%;
      /* Degradado usando el Guinda y Dorado oficiales */
      background: linear-gradient(to right, #75203D 0%, #C9A15B 50%, #75203D 100%);
    }

    /* --- ESTILOS DEL PANEL CENTRAL --- */
    .pantalla-inicio {
      max-width: 800px;
      margin: 60px auto; 
      text-align: center;
      padding: 60px 40px;
    }
    
    .pantalla-inicio h1 {
      font-size: 2.8rem;
      color: var(--primary); 
      margin-bottom: 20px;
    }
    
    .pantalla-inicio p {
      font-size: 1.1rem;
      color: var(--muted);
      line-height: 1.7;
      margin-bottom: 40px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }

    .btn-inicio {
      width: auto;
      padding: 16px 45px;
      font-size: 1.1rem;
      text-decoration: none;
      display: inline-block;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    @media (max-width: 768px) {
      .pantalla-inicio {
        margin: 30px 15px;
        padding: 40px 20px;
      }
    }
  </style>
</head>
<body>

  <!-- ENCABEZADO CON LOGOS -->
  <header class="header-oficial">
    <div class="logos-contenedor">
      <img src="../IMG/loco (1) (1).png" alt="Gobierno del Estado de Veracruz" class="logo-img">
      <img src="../IMG/amor-veracruz.png" alt="Por amor a Veracruz" class="logo-img">
    </div>
  </header>

  <!-- LÍNEA DE COLORES -->
  <div class="linea-colores"></div>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="panel pantalla-inicio">
    <h1>CiudadReporta</h1>
    <p>Bienvenido a la plataforma de reportes ciudadanos de Obras Públicas. Aquí puedes compartir incidencias, comunicar problemas y contribuir a una ciudad más segura y ordenada.</p>
    
    <!-- Botón que dirige al formulario -->
    <a class="btn btn-inicio" href="Pagina.php">Hacer un reporte</a>
  </main>

</body>
</html>

<?php
  $widgetIA = __DIR__ . '/chat_ia.php';
  if (file_exists($widgetIA)) {
      include $widgetIA;
  }
  ?>
</body>