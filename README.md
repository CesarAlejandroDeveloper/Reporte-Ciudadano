# 🏛️ CiudadReporta — Plataforma de Atención Ciudadana y Mapa de Calor Urbano

**CiudadReporta** es una plataforma web integral desarrollada para la **Heroica Veracruz, Veracruz**, que permite a los ciudadanos reportar incidencias urbanas con evidencia fotográfica y geolocalización precisa, consultar el seguimiento en tiempo real mediante folio, interactuar con un asistente virtual impulsado por IA local (**Respondin**) y visualizar zonas críticas mediante un **Mapa de Calor** dinámico.

---

## ✨ Características Principales

### 1. Portal Ciudadano (`vista/Home.php`, `vista/Pagina.php`, `vista/Reporte.php`)
* **Registro geolocalizado:** Mapa interactivo basado en **MapLibre GL JS** con búsqueda de direcciones vía **Nominatim (OpenStreetMap)** y soporte de **GPS**.
* **Validación geográfica de costa:** Filtro poligonal integrado que impide registrar coordenadas dentro del Golfo de México o fuera del área urbana de Veracruz.
* **Evidencia fotográfica segura:** Carga de **1 a 3 imágenes** (JPG, PNG o WEBP, máx. 8 MB c/u) verificadas mediante inspección de firma binaria (`finfo` y encabezados mágicos).
* **Vista previa optimizada:** Muestra los **5 reportes más recientes** por defecto (con opción de expandir el historial completo) para mantener una interfaz limpia y rápida.
* **Seguimiento por Folio:** Consulta pública mediante código único (`RC-DDMMYY-XXXX`) con línea de tiempo de 4 etapas (`Reporte Recibido` → `En Revisión` → `En Proceso` → `Resuelto`) y alertas de estado.
* **Control flexible para densidad urbana:** Configurado para permitir hasta **150 reportes por hora por dispositivo** y acumulación en una misma zona, facilitando la alimentación del mapa de calor.

### 2. Panel Administrativo (`vista/Login.php`, `vista/Admin.php`)
* **Autenticación segura:** Inicio de sesión con sesiones PHP regeneradas y migración automática de contraseñas a hash seguro **`bcrypt` (`password_hash`)**.
* **Gestión individual y masiva:** Selección múltiple de reportes para actualizar estatus (`En revisión`, `En proceso`, `Resuelto`), dar de baja (`Denegada`) o **eliminar definitivamente** (limpiando registros en BD, historial, notificaciones y archivos físicos en `uploads/`).

### 3. Asistente Virtual IA — *Respondin* (`vista/chat_ia.php`, `api/ia/chatbot.php`)
* **Widget flotante integrado:** Disponible en el portal ciudadano con aceptación de términos, sugerencias rápidas y persistencia de sesión.
* **Integración con Ollama:** Procesamiento de lenguaje natural local para resolver dudas sobre trámites, consultar estatus de folios y recibir reportes con ubicación GPS y fotografía adjunta.

### 4. Mapa de Calor en Tiempo Real (`api/mapacalor/index.html`)
* **Agrupación espacial:** Cuadrícula dinámica de **300 metros** que clasifica automáticamente las zonas según su densidad de incidencias (*Moderado*, *Notable*, *Grave*) con actualización periódica.

---

## 🛠️ Tecnologías Utilizadas

* **Backend:** PHP 8.x (PDO, `finfo`, sesiones nativas, API REST JSON).
* **Base de Datos:** MySQL / MariaDB (`reporte_ciudadano`).
* **Frontend:** HTML5, CSS3 responsivo, JavaScript (ES6+ / Fetch API).
* **Mapas y Geolocalización:** MapLibre GL JS 3.6, OpenStreetMap Tiles, Nominatim API.
* **Inteligencia Artificial:** Ollama (ejecución local) + API REST interna protegida por token.

---

## 📁 Estructura del Proyecto

```text
htdocs/
├── .gitignore
├── README.md
├── api/
│   ├── ia/
│   │   ├── chatbot.php          # Endpoint conversacional del asistente Respondin
│   │   ├── estatus.php          # Consulta y actualización de estatus protegida por API Key
│   │   └── uploads/             # Evidencias adjuntadas desde el chat IA
│   └── mapacalor/
│       └── index.html           # Visualizador de densidad de reportes (cuadrantes de 300m)
└── CiudadReporta/
    ├── index.php                # Enrutador principal de la API REST y redirección a Home
    ├── controlador/
    │   └── ReporteController.php # Lógica de negocio, validación, auth y CRUD de reportes
    ├── modelo/
    │   ├── config.php           # Constantes de BD, tokens, CORS y límites por dispositivo
    │   └── Conexion.php         # Conexión PDO a MySQL
    ├── vista/
    │   ├── Home.php             # Página de inicio del portal
    │   ├── Pagina.php           # Formulario de nuevo reporte y lista de 5 recientes
    │   ├── Reporte.php          # Detalle y línea de tiempo por folio o ID
    │   ├── Login.php            # Acceso para administradores
    │   ├── Admin.php            # Panel de gestión y acciones masivas
    │   └── chat_ia.php          # Componente flotante de Respondin
    ├── CSS/
    │   └── styles.css           # Hoja de estilos institucional
    └── uploads/                 # Evidencias fotográficas de los reportes (protegido con .htaccess)
```

---

## 🚀 Instalación y Configuración Local (XAMPP)

### 1. Clonar o ubicar el proyecto
Coloca la carpeta del repositorio dentro del directorio público de tu servidor local (`C:\xampp\htdocs\`).

### 2. Configurar la Base de Datos
1. Inicia **Apache** y **MySQL** en el panel de control de XAMPP.
2. Abre **phpMyAdmin** (`http://localhost/phpmyadmin`) y crea la base de datos **`reporte_ciudadano`** con cotejamiento `utf8mb4_unicode_ci`.
3. Importa el esquema SQL de las tablas principales (`reportes`, `usuarios_admin`, `historial_estatus`, `notificaciones`, `ciudadanos`).
4. Crea un usuario administrador inicial (si aún no existe):
   ```sql
   INSERT INTO usuarios_admin (nombre, correo, contrasena)
   SELECT 'Administrador', 'admin@ciudadreporta.gob.mx', 'admin123'
   WHERE NOT EXISTS (
       SELECT 1 FROM usuarios_admin WHERE correo = 'admin@ciudadreporta.gob.mx'
   );
   ```
   > *Nota: Al iniciar sesión por primera vez, el sistema cifrará automáticamente la contraseña con `bcrypt`.*

### 3. Ajustar parámetros en `CiudadReporta/modelo/config.php`
Verifica las credenciales y parámetros de operación:
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'reporte_ciudadano');
define('DB_USER', 'root');
define('DB_PASS', '');

define('COOLDOWN_SEGUNDOS_DISPOSITIVO', 2);
define('MAX_REPORTES_POR_HORA_DISPOSITIVO', 150);
define('BLOQUEAR_DUPLICADOS_ZONA', false);
```

### 4. Acceder al sistema
* **Portal Ciudadano:** `http://localhost/CiudadReporta/vista/Home.php`
* **Crear Reporte:** `http://localhost/CiudadReporta/vista/Pagina.php`
* **Panel Administrativo:** `http://localhost/CiudadReporta/vista/Login.php`
* **Mapa de Calor:** `http://localhost/api/mapacalor/index.html`

---

## 🔌 Endpoints Principales de la API (`CiudadReporta/index.php`)

| Método | Ruta | Descripción | Acceso |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/reportes?publico=1` | Lista los reportes activos (soporta `&limit=N`) | Público |
| `GET` | `/api/reportes?folio=RC-...` | Obtiene el detalle e historial de un reporte por folio o `id` | Público |
| `POST` | `/api/reportes` | Registra un nuevo reporte (`multipart/form-data` con 1 a 3 fotos) | Público |
| `POST` | `/api/login` | Inicia sesión administrativa y genera cookie de sesión | Público |
| `GET` | `/api/session` | Verifica si existe una sesión administrativa activa | Admin |
| `POST` | `/api/logout` | Cierra la sesión administrativa actual | Admin |
| `POST` | `/api/reportes/estatus` | Actualiza estatus/prioridad o elimina reportes (`id` o `ids[]`) | Admin |

---

## 🔒 Seguridad Implementada

* **Protección de cargas (`uploads/.htaccess`):** Desactiva la ejecución de scripts PHP dentro de los directorios de imágenes subidas por usuarios.
* **Validación binaria de archivos:** Verifica el tipo MIME real mediante `fileinfo` y dimensiones válidas con `getimagesize()`.
* **Cabeceras CORS dinámicas:** Restringidas al host activo (soporta `localhost`, `127.0.0.1` e IPs de red local para pruebas en dispositivos móviles).
* **Sanitización de entradas:** Limpieza de caracteres de control Unicode y escape de salida HTML en el frontend para prevenir XSS.