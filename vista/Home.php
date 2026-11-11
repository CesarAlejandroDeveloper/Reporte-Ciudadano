<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CiudadReporta</title>
    <style>
      :root {
        --bg: #f4f7fb;
        --panel: #ffffff;
        --primary: #1f6feb;
        --text: #1f2937;
        --muted: #6b7280;
        --border: #e5e7eb;
      }

      * { box-sizing: border-box; }

      body {
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        background: linear-gradient(135deg, #eef4ff, var(--bg));
        color: var(--text);
      }

      .container {
        max-width: 960px;
        margin: 60px auto;
        padding: 32px;
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(31, 111, 235, 0.08);
      }

      h1 {
        margin: 0 0 16px;
        font-size: 2.2rem;
        color: var(--primary);
      }

      p {
        font-size: 1rem;
        line-height: 1.7;
        color: var(--muted);
      }

      .btn {
        display: inline-block;
        margin-top: 18px;
        padding: 12px 20px;
        background: var(--primary);
        color: #fff;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
      }
    </style>
  </head>
  <body>
    <main class="container">
      <h1>CiudadReporta</h1>
      <p>Bienvenido a la plataforma de reportes ciudadanos. Aquí puedes compartir incidencias, comunicar problemas y contribuir a una ciudad más segura y ordenada.</p>
      <a class="btn" href="#">Iniciar</a>
    </main>
  </body>
</html>
