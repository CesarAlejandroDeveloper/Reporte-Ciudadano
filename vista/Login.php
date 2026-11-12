<?php
/**
 * Vista de Inicio de Sesión Administrativo - CiudadReporta
 *
 * Verifica si ya existe una sesión activa para redirigir directamente al panel
 * administrativo (Admin.php) y proporciona el formulario de autenticación con
 * manejo de errores y visualización opcional de contraseña.
 *
 * @package CiudadReporta\Vista
 */
session_start();
if (isset($_SESSION['admin_id']) && intval($_SESSION['admin_id']) > 0) {
    header('Location: Admin.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Acceso Administrador - CiudadReporta</title>
  <link rel="stylesheet" href="../CSS/styles.css" />
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f3f4f6;
      font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
      padding: 16px;
      box-sizing: border-box;
    }

    .login-card {
      width: 100%;
      max-width: 410px;
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 32px 28px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
    }

    .login-header {
      text-align: center;
      margin-bottom: 22px;
    }

    .login-header h1 {
      margin: 0 0 6px;
      color: var(--primary, #691c32);
      font-size: 1.7rem;
      font-weight: 800;
    }

    .login-header p {
      margin: 0;
      color: #6b7280;
      font-size: 0.92rem;
    }

    .alerta-login {
      display: none;
      background: #fee2e2;
      color: #b91c1c;
      border: 1px solid #fca5a5;
      border-radius: 10px;
      padding: 12px 14px;
      font-size: 0.88rem;
      text-align: center;
      margin-bottom: 18px;
      line-height: 1.4;
    }

    .alerta-login.visible {
      display: block;
    }

    .campo-grupo {
      margin-bottom: 16px;
    }

    .campo-grupo label {
      display: block;
      font-weight: 700;
      font-size: 0.9rem;
      color: #1f2937;
      margin-bottom: 6px;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .campo-input {
      width: 100%;
      padding: 11px 14px;
      border: 1px solid #d1d5db;
      border-radius: 9px;
      font-size: 0.96rem;
      color: #111827;
      background: #ffffff;
      box-sizing: border-box;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .campo-input:focus {
      outline: none;
      border-color: var(--primary, #691c32);
      box-shadow: 0 0 0 3px rgba(105, 28, 50, 0.14);
    }

    .btn-ver-pass {
      position: absolute;
      right: 10px;
      background: transparent;
      border: none;
      color: #6b7280;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
      padding: 4px 6px;
    }

    .btn-ver-pass:hover {
      color: var(--primary, #691c32);
    }

    .btn-entrar {
      width: 100%;
      padding: 12px;
      margin-top: 6px;
      background: var(--primary, #691c32);
      color: #ffffff;
      border: none;
      border-radius: 9px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.15s ease, opacity 0.15s ease;
    }

    .btn-entrar:hover:not(:disabled) {
      background: #521426;
    }

    .btn-entrar:disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }

    .login-footer {
      text-align: center;
      margin-top: 18px;
    }

    .login-footer a {
      color: #6b7280;
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 600;
    }

    .login-footer a:hover {
      color: var(--primary, #691c32);
    }
  </style>
</head>
<body>
  <main class="login-card">
    <div class="login-header">
      <h1>CiudadReporta</h1>
      <p>Acceso al Panel de Administración</p>
    </div>

    <div id="alertaLogin" class="alerta-login" role="alert"></div>

    <form id="formLoginAdmin" novalidate>
      <div class="campo-grupo">
        <label for="usuario">Nombre o Correo electrónico</label>
        <input
          type="text"
          id="usuario"
          name="usuario"
          class="campo-input"
          placeholder="admin@ciudadreporta.gob.mx"
          autocomplete="username"
          required
        />
      </div>

      <div class="campo-grupo">
        <label for="contrasena">Contraseña</label>
        <div class="input-wrapper">
          <input
            type="password"
            id="contrasena"
            name="contrasena"
            class="campo-input"
            style="padding-right: 68px;"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
          <button type="button" id="btnTogglePass" class="btn-ver-pass">Mostrar</button>
        </div>
      </div>

      <button type="submit" id="btnIniciarSesion" class="btn-entrar">
        Iniciar Sesión
      </button>
    </form>

    <div class="login-footer">
      <a href="Home.php">← Volver al portal ciudadano</a>
    </div>
  </main>

  <script>
    /**
     * Controla el envío del formulario de autenticación hacia el endpoint /api/login
     * y redirige al panel de administración cuando las credenciales son válidas.
     */
    (function () {
      const API_LOGIN = '../index.php/api/login';

      const form       = document.getElementById('formLoginAdmin');
      const inputUser  = document.getElementById('usuario');
      const inputPass  = document.getElementById('contrasena');
      const btnSubmit  = document.getElementById('btnIniciarSesion');
      const btnToggle  = document.getElementById('btnTogglePass');
      const alertaBox  = document.getElementById('alertaLogin');

      /**
       * Muestra un mensaje de error en la tarjeta de inicio de sesión.
       * @param {string} mensaje Texto descriptivo del error.
       */
      function mostrarError(mensaje) {
        alertaBox.textContent = mensaje;
        alertaBox.classList.add('visible');
      }

      /**
       * Oculta el banner de alerta.
       */
      function ocultarError() {
        alertaBox.textContent = '';
        alertaBox.classList.remove('visible');
      }

      // Alternar visibilidad de la contraseña
      btnToggle.addEventListener('click', () => {
        const esPassword = inputPass.type === 'password';
        inputPass.type = esPassword ? 'text' : 'password';
        btnToggle.textContent = esPassword ? 'Ocultar' : 'Mostrar';
      });

      // Envío de credenciales
      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        ocultarError();

        const usuario    = inputUser.value.trim();
        const contrasena = inputPass.value;

        if (!usuario || !contrasena) {
          mostrarError('Por favor ingresa tu nombre o correo y tu contraseña.');
          return;
        }

        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Verificando...';

        try {
          const respuesta = await fetch(API_LOGIN, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({ usuario, contrasena })
          });

          const rawText = await respuesta.text();
          let data;
          try {
            data = JSON.parse(rawText);
          } catch (_) {
            throw new Error('Respuesta inesperada del servidor.');
          }

          if (respuesta.ok) {
            window.location.href = 'Admin.php';
            return;
          }

          mostrarError(data.error || 'Credenciales incorrectas. Verifica tu usuario/correo y contraseña.');
        } catch (error) {
          mostrarError(error.message || 'No se pudo conectar con el servidor.');
        } finally {
          btnSubmit.disabled = false;
          btnSubmit.textContent = 'Iniciar Sesión';
        }
      });
    })();
  </script>
</body>
</html>