-- ══════════════════════════════════════════════════════════════════
-- C.A.A.S. v3 — Script de actualización
-- Ejecutar en phpMyAdmin → caas_v2 → SQL
-- Solo agrega tablas y columnas nuevas, NO toca los datos existentes
-- ══════════════════════════════════════════════════════════════════

USE caas_v2;

-- ── Tabla para tokens de recuperación de contraseña ───────────────
-- Guarda el hash del token (nunca el token real), con expiración de 30 min
CREATE TABLE IF NOT EXISTS reset_password (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    id_usuario  INT UNSIGNED    NOT NULL,
    token_hash  VARCHAR(64)     NOT NULL,   -- SHA-256 del token enviado por email
    expira_en   DATETIME        NOT NULL,   -- 30 minutos desde la creación
    usado       TINYINT(1)      NOT NULL DEFAULT 0, -- 0=disponible, 1=ya usado
    creado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_token (token_hash),
    CONSTRAINT fk_reset_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Columnas nuevas en empresa para el mapa ───────────────────────
-- latitud y longitud del local para mostrar en Leaflet
ALTER TABLE empresa
    ADD COLUMN IF NOT EXISTS latitud    DECIMAL(10,7) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS longitud   DECIMAL(10,7) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS descripcion TEXT          NULL DEFAULT NULL;

-- ── Columnas nuevas en cliente para el perfil ─────────────────────
ALTER TABLE cliente
    ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) NULL DEFAULT NULL;

-- ── Índice para limpiar tokens expirados fácilmente ───────────────
CREATE INDEX IF NOT EXISTS idx_reset_expira ON reset_password(expira_en);

-- ── Limpiar tokens viejos automáticamente (evento programado) ─────
-- Elimina tokens expirados de más de 24 horas para mantener la tabla limpia
CREATE EVENT IF NOT EXISTS limpiar_tokens_reset
    ON SCHEDULE EVERY 1 HOUR
    DO DELETE FROM reset_password WHERE expira_en < NOW() - INTERVAL 24 HOUR;
