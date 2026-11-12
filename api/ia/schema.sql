-- Ejecutar en phpMyAdmin → base reporte_ciudadano → pestaña SQL
-- (XAMPP usa MariaDB; "IF NOT EXISTS" es válido). La tabla reportes está vacía, así que es seguro.
USE reporte_ciudadano;

-- Ajustes a la tabla existente `reportes`
ALTER TABLE reportes
  MODIFY titulo VARCHAR(150) NULL,
  MODIFY descripcion TEXT NULL,
  MODIFY categoria VARCHAR(40) NULL,
  MODIFY prioridad VARCHAR(20) NULL,
  MODIFY latitud DECIMAL(10,7) NULL,
  MODIFY longitud DECIMAL(10,7) NULL,
  MODIFY foto_url VARCHAR(255) NULL,
  MODIFY estatus VARCHAR(30) NOT NULL DEFAULT 'reporte recibido',
  ADD COLUMN IF NOT EXISTS folio VARCHAR(20) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS ciudadano_id INT NULL,
  ADD COLUMN IF NOT EXISTS area_derivada VARCHAR(20) NULL,
  ADD UNIQUE INDEX IF NOT EXISTS uq_reportes_folio (folio);

CREATE TABLE IF NOT EXISTS ciudadanos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NULL,
  telefono VARCHAR(20) NOT NULL UNIQUE,      -- WhatsApp, solo dígitos
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS conversaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(64) NOT NULL UNIQUE,
  datos LONGTEXT NOT NULL,                   -- JSON con lo que lleva recopilado la IA
  acepto_terminos TINYINT(1) NOT NULL DEFAULT 0,
  ip VARCHAR(45) NULL,
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mensajes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversacion_id INT NOT NULL,
  rol VARCHAR(12) NOT NULL,                  -- user | assistant
  contenido TEXT NOT NULL,
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (conversacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS historial_estatus (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reporte_id INT NOT NULL,
  estatus VARCHAR(30) NOT NULL,
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (reporte_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cola de avisos por WhatsApp (enviado=0 → pendiente; se enviará en la siguiente etapa)
CREATE TABLE IF NOT EXISTS notificaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reporte_id INT NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  estatus VARCHAR(30) NOT NULL,
  mensaje VARCHAR(300) NOT NULL,
  enviado TINYINT(1) NOT NULL DEFAULT 0,
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (enviado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
