# CiudadReporta — Plataforma de Atención y Reporte Ciudadano

**CiudadReporta** es una aplicación web integral diseñada para facilitar el reporte, seguimiento y gestión de incidencias urbanas (baches, luminarias, fugas de agua, acumulación de basura, entre otros) en la zona conurbada de **Heroica Veracruz, Veracruz, México**.

El sistema conecta a la ciudadanía con las áreas de atención municipal mediante un portal interactivo con geolocalización, un mapa de calor dinámico por densidad de reportes, un panel administrativo con acciones masivas y un asistente virtual impulsado por Inteligencia Artificial local (**Respondin**).

---

## Características Principales

### Portal Ciudadano
* **Registro geolocalizado:** Selección de ubicación mediante mapa interactivo (MapLibre GL + OpenStreetMap), búsqueda de calles con Nominatim o GPS del dispositivo.
* **Validación geográfica de costa:** Filtro matemático que impide registrar incidencias fuera del polígono terrestre de Veracruz o sobre el Golfo de México.
* **Evidencia fotográfica segura:** Carga de 1 a 3 fotografías (JPG, PNG o WEBP, máximo 8 MB por archivo) con verificación de firma binaria (*magic bytes*) tanto en cliente como en servidor.
* **Seguimiento por folio único:** Consulta pública mediante folio (`RC-DDMMAA-XXXX`) con línea de tiempo de estados (`Reporte recibido` → `En revisión` → `En proceso` → `Resuelto` / `Dado de baja`).
* **Vista optimizada:** Muestra los 5 reportes más recientes por defecto para mantener una interfaz limpia, con opción para desplegar el historial completo.

### Mapa de Calor (`api/mapacalor`)
* Visualización geoespacial de zonas críticas agrupadas en cuadrantes de ~300 metros.
* Clasificación automática por densidad de incidencias (*Moderado*, *Notable*, *Grave*) con actualización periódica en tiempo real.

### Panel Administrativo (`vista/Admin.php`)
* Autenticación protegida mediante sesiones PHP (`session_regenerate_id`) y cifrado de contraseñas con `bcrypt` (`password_hash` / `password_verify`).
* Gestión individual y **selección múltiple (acciones masivas)** para cambiar estatus, dar de baja reportes o eliminarlos definitivamente (incluyendo limpieza de archivos en disco).

### Asistente Virtual "Respondin" (`api/ia`)
* Widget flotante integrado en el portal ciudadano (`vista/chat_ia.php`).
* Permite a los ciudadanos resolver dudas sobre trámites, consultar el estatus de su folio o levantar reportes asistidos adjuntando ubicación GPS y fotografía.

---

## Tecnologías Utilizadas

* **Backend:** PHP 8+ (arquitectura modular con enrutamiento REST en `index.php`), PDO.
* **Base de Datos:** MySQL / MariaDB (`reporte_ciudadano`).
* **Frontend:** HTML5, CSS3, JavaScript (ES6+ Vanilla).
* **Mapas y Geocodificación:** MapLibre GL JS, OpenStreetMap Tiles, API de Nominatim.
* **Inteligencia Artificial:** Ollama (ejecución de modelos de lenguaje en entorno local).
* **Servidor Local:** Apache (XAMPP).

---

## Estructura del Proyecto

```text
├── CiudadReporta/
│   ├── index.php                  # Enrutador principal de vistas y endpoints /api/*
│   ├── CSS/
│   │   └── styles.css             # Estilos globales del portal
│   ├── controlador/
│   │   └── ReporteController.php  # Lógica de negocio, autenticación, CRUD y validaciones
│   ├── modelo/
│   │   ├── config.php             # Credenciales BD, tokens, CORS y límites de publicación
│   │   └── Conexion.php           # Conexión PDO a MySQL
│   ├── vista/
│   │   ├── Home.php               # Página de inicio y accesos principales
│   │   ├── Pagina.php             # Formulario de nuevo reporte y listado reciente (top 5)
│   │   ├── Reporte.php            # Vista detallada y línea de tiempo por folio/ID
│   │   ├── Login.php              # Acceso al panel de administración
│   │   ├── Admin.php              # Gestión de reportes y acciones masivas
│   │   └── chat_ia.php            # Componente flotante del asistente Respondin
│   └── uploads/                   # Almacenamiento de evidencias (protegido con .htaccess)
├── api/
│   ├── ia/                        # Backend del chatbot Respondin y conexión con Ollama
│   └── mapacalor/                 # Módulo de visualización de densidad y mapa de calor
└── README.md
```

---

## Instalación y Configuración Local

1. **Clonar el repositorio** dentro de la carpeta pública de tu servidor local (por ejemplo, `C:\xampp\htdocs\`):
   ```bash
   git clone https://github.com/tu-usuario/reporte-ciudadano.git
   ```
2. **Iniciar servicios:** Abre el panel de control de **XAMPP** e inicia **Apache** y **MySQL**.
3. **Configurar la base de datos:**
   * Crea una base de datos llamada `reporte_ciudadano` en **phpMyAdmin** (`utf8mb4_unicode_ci`).
   * Importa el esquema SQL de las tablas (`reportes`, `usuarios_admin`, `historial_estatus`, `notificaciones`, `ciudadanos`).
4. **Revisar parámetros del sistema:**
   * Edita `CiudadReporta/modelo/config.php` si necesitas ajustar las credenciales de MySQL (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`), el token de API (`API_ADMIN_TOKEN`) o los límites de envío por dispositivo (`MAX_REPORTES_POR_HORA_DISPOSITIVO`).
5. **Activar el Asistente IA (Opcional):**
   * Instala [Ollama](https://ollama.com/) y asegúrate de que el servicio local esté en ejecución antes de interactuar con la burbuja de chat.
6. **Acceder a la aplicación:**
   * Portal ciudadano: `http://localhost/CiudadReporta/index.php`
   * Panel administrativo: `http://localhost/CiudadReporta/vista/Login.php`

---

## Transparencia y Uso de Inteligencia Artificial (IA)

En cumplimiento con las buenas prácticas de desarrollo de software y transparencia académica/técnica, se documenta el uso de herramientas de Inteligencia Artificial en este proyecto en dos niveles:

1. **Como componente funcional del sistema:**
   * La plataforma integra un asistente conversacional (**Respondin**) respaldado por modelos de lenguaje locales mediante **Ollama** para orientar a los ciudadanos, consultar folios y asistir en la captura de incidencias.
2. **Como herramienta de apoyo durante el desarrollo:**
   * Durante la construcción del proyecto se utilizó asistencia de Inteligencia Artificial como herramienta de apoyo (*pair programming*) para:
     * Revisión y refactorización de código en PHP, JavaScript y consultas SQL.
     * Implementación de controles de seguridad (sanitización de entradas, protección CORS, validación binaria de archivos, migración de contraseñas a `bcrypt` y control de sesiones).
     * Depuración de errores de integración entre el frontend, la API REST y el módulo de mapa de calor.
     * Estructuración de la documentación técnica del proyecto.
   * **Supervisión humana:** Todas las decisiones de arquitectura, diseño de interfaz, reglas de negocio, pruebas funcionales en entorno local y validación final del código fueron realizadas, verificadas e integradas por el equipo de desarrollo.

## Problema que Resuelve
En la zona conurbada de Heroica Veracruz, el reporte de incidencias urbanas (baches, luminarias fundidas, fugas de agua o acumulación de basura) suele carecer de canales centralizados, ubicación geográfica exacta y mecanismos transparentes de seguimiento. Esto provoca reportes duplicados, falta de evidencia visual, incidencias registradas con coordenadas erróneas y desconocimiento ciudadano sobre si su solicitud fue atendida.

## Personas Usuarias
- **Ciudadanía general (Habitantes de Veracruz):** Personas que detectan una falla urbana en la vía pública y necesitan reportarla rápido desde su celular o computadora con ubicación exacta, fotos y un folio para dar seguimiento sin trámites presenciales complejos.
- **Personal y Administradores Municipales:** Operadores encargados de validar evidencias, filtrar reportes duplicados o improcedentes, actualizar el estatus de atención y detectar zonas críticas mediante el mapa de calor.

## Cómo se Usa

### Flujo Ciudadano
1. **Crear un reporte:** Ingresa al portal principal (`vista/Pagina.php`), selecciona la categoría de la incidencia (bache, fuga, luminaria, etc.) y marca el punto exacto en el mapa interactivo, usando el buscador de calles o el botón de GPS.
2. **Adjuntar evidencia:** Sube de 1 a 3 fotografías de respaldo y envía el formulario.
3. **Dar seguimiento:** Guarda el folio único generado (`RC-DDMMAA-XXXX`) e ingrésalo en la vista de consulta (`vista/Reporte.php`) para revisar la línea de tiempo de atención.
4. **Asistencia con IA:** Haz clic en el botón flotante de **Respondin** para resolver dudas sobre trámites, consultar un folio por texto o levantar un reporte guiado.

### Flujo Administrativo
1. **Iniciar sesión:** Accede a `vista/Login.php` con las credenciales de administrador.
2. **Gestionar incidencias:** Desde `vista/Admin.php`, filtra los reportes recibidos, revisa las fotografías adjuntas y cambia el estatus individualmente o mediante selección múltiple (`En revisión`, `En proceso`, `Resuelto` o `Dado de baja`).
3. **Analizar zonas críticas:** Consulta el módulo de mapa de calor para identificar cuadrantes de 300 m con alta concentración de reportes.

## Datos Utilizados
- **Cartografía y Geocodificación:** Teselas vectoriales/ráster de **OpenStreetMap** renderizadas con **MapLibre GL JS** y servicio de geocodificación directa e inversa mediante la **API pública de Nominatim** para convertir direcciones en coordenadas.
- **Delimitación Geográfica (Polígono Costero):** Coordenadas geográficas de referencia de la línea de costa y zona urbana de Heroica Veracruz utilizadas para validar matemáticamente que ningún reporte se registre sobre el Golfo de México.
- **Datos de Prueba y Operación:** Registros sintéticos/simulados de incidencias urbanas en Veracruz para validar el funcionamiento del mapa de calor, el historial de estatus y las pruebas de carga. El sistema no recolecta ni almacena datos personales sensibles de los ciudadanos.

---

## Equipo de Desarrollo

* **Autor(es):** *Barragan Diaz Cesar Alejandro, Escamilla De La Hoz Alberto, Valera Galicia Osvaldo, Rico Balderas Ezequiel, Gonzales Castro Eduardo*
* **Ubicación:** Veracruz, Veracruz, México.
