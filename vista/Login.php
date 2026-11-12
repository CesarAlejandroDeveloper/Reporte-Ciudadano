<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Acceso Administrador - CiudadReporta</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background-color: #f3f4f6;
      padding: 16px;
    }
    .login-card {
      background: var(--panel, #ffffff);
      width: 100%;
      max-width: 420px;
      padding: 32px 24px;
      border-radius: 12px;
      border: 1px solid var(--border, #e5e7eb);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }
    .login-header {
      text-align: center;
      margin-bottom: 24px;
    }
    .login-header h1 {
      color: var(--primary, #691c32);
      font-size: 1.6rem;
      margin: 0 0 6px 0;
    }
    .login-header p {
      color: var(--muted, #6b7280);
      font-size: 0.9rem;
      margin: 0;
    }
    .form-group {
      margin-bottom: 18px;
    }
    .form-group label {
      display: block;
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--text, #374151);
      margin-bottom: 6px;
    }
    .input-login {
      width: 100%;
      padding: 12px 14px;
      font-size: 1rem;
      border: 1px solid var(--border, #d1d5db);
      border-radius: 8px;
      box-sizing: border-box;
    }
    .input-login:focus {
      outline: 2px solid var(--primary, #691c32);
      border-color: transparent;
    }
    .btn-login {
      width: 100%;
      padding: 14px;
      font-size: 1rem;
      font-weight: 700;
      background: var(--primary, #691c32);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 8px;
    }
    .btn-login:active {
      opacity: 0.9;
    }
    .error-box {
      display: none;
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 0.88rem;
      margin-bottom: 18px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-header">
      <h1>CiudadReporta</h1>
      <p>Acceso al Panel de Administración</p>
    </div>

    <div id="mensajeError" class="error-box"></div>

    <form id="formularioLogin">
      <div class="form-group">
        <label for="usuario">Nombre o Correo electrónico</label>
        <input type="text" id="usuario" name="usuario" class="input-login" placeholder="Ej: admin o admin@ciudadreporta.gob.mx" required autocomplete="username">
      </div>

      <div class="form-group">
        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" class="input-login" placeholder="••••••••" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn-login" id="btnSubmit">Iniciar Sesión</button>
    </form>

    <div style="text-align: center; margin-top: 20px;">
      <a href="Pagina.php" style="color: var(--muted, #6b7280); font-size: 0.85rem; text-decoration: none;">← Volver al portal ciudadano</a>
    </div>
  </div>

  <script>
    const API_LOGIN = '../index.php/api/login';

    document.getElementById('formularioLogin').addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const usuario = document.getElementById('usuario').value.trim();
      const contrasena = document.getElementById('contrasena').value.trim();
      const mensajeError = document.getElementById('mensajeError');
      const btnSubmit = document.getElementById('btnSubmit');

      mensajeError.style.display = 'none';
      btnSubmit.disabled = true;
      btnSubmit.innerText = 'Verificando...';

      try {
        const respuesta = await fetch(API_LOGIN, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ usuario, contrasena })
        });

        const resultado = await respuesta.json();

        if (respuesta.ok) {
          window.location.href = 'Admin.php';
        } else {
          mensajeError.innerText = resultado.error || 'Error al iniciar sesión.';
          mensajeError.style.display = 'block';
        }
      } catch (error) {
        console.error("Error al iniciar sesión:", error);
        mensajeError.innerText = 'No se pudo conectar con el servidor.';
        mensajeError.style.display = 'block';
      } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'Iniciar Sesión';
      }
    });
  </script>
</body>
</html>