-- ══════════════════════════════════════════════════════════════════
-- C.A.A.S. v4 — Escala Nacional Uruguay
-- Ejecutar en phpMyAdmin → caas_v2 → SQL
-- ══════════════════════════════════════════════════════════════════
USE caas_v2;

-- ── Departamento y localidad para usuarios ─────────────────────────
ALTER TABLE usuario
    ADD COLUMN IF NOT EXISTS departamento VARCHAR(50)  NULL DEFAULT NULL AFTER telefono,
    ADD COLUMN IF NOT EXISTS localidad    VARCHAR(100) NULL DEFAULT NULL AFTER departamento;

-- ── Departamento y localidad para empresas (búsqueda geográfica) ───
ALTER TABLE empresa
    ADD COLUMN IF NOT EXISTS departamento  VARCHAR(50)  NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS localidad     VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS modo_turista  TINYINT(1)   NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS plato_tipico  VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ── Tabla de stats de delivery por empresa ─────────────────────────
CREATE TABLE IF NOT EXISTS empresa_stats (
    id_empresa         INT UNSIGNED NOT NULL,
    pedidos_totales    INT UNSIGNED NOT NULL DEFAULT 0,
    en_preparacion     INT UNSIGNED NOT NULL DEFAULT 0,
    en_camino          INT UNSIGNED NOT NULL DEFAULT 0,
    entregados         INT UNSIGNED NOT NULL DEFAULT 0,
    cancelados         INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                       ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_empresa),
    CONSTRAINT fk_stats_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresa(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Inicializar stats para empresas existentes ─────────────────────
INSERT IGNORE INTO empresa_stats (id_empresa)
    SELECT id_empresa FROM empresa;

-- ── Trigger: actualizar stats al insertar/actualizar pedido ────────
DROP TRIGGER IF EXISTS trg_pedido_stats_insert;
CREATE TRIGGER trg_pedido_stats_insert
AFTER INSERT ON pedido
FOR EACH ROW
BEGIN
    INSERT INTO empresa_stats (id_empresa, pedidos_totales, en_preparacion)
    VALUES (NEW.id_empresa, 1, IF(NEW.estado = 'En preparación', 1, 0))
    ON DUPLICATE KEY UPDATE
        pedidos_totales = pedidos_totales + 1,
        en_preparacion  = en_preparacion  + IF(NEW.estado = 'En preparación', 1, 0);
END;

DROP TRIGGER IF EXISTS trg_pedido_stats_update;
CREATE TRIGGER trg_pedido_stats_update
AFTER UPDATE ON pedido
FOR EACH ROW
BEGIN
    -- Restar del estado anterior
    UPDATE empresa_stats SET
        en_preparacion = en_preparacion - IF(OLD.estado = 'En preparación', 1, 0),
        en_camino      = en_camino      - IF(OLD.estado = 'En camino',      1, 0),
        entregados     = entregados     - IF(OLD.estado = 'Entregado',      1, 0),
        cancelados     = cancelados     - IF(OLD.estado = 'Cancelado',      1, 0)
    WHERE id_empresa = NEW.id_empresa;
    -- Sumar al estado nuevo
    UPDATE empresa_stats SET
        en_preparacion = en_preparacion + IF(NEW.estado = 'En preparación', 1, 0),
        en_camino      = en_camino      + IF(NEW.estado = 'En camino',      1, 0),
        entregados     = entregados     + IF(NEW.estado = 'Entregado',      1, 0),
        cancelados     = cancelados     + IF(NEW.estado = 'Cancelado',      1, 0)
    WHERE id_empresa = NEW.id_empresa;
END;

-- ── Índices para búsqueda geográfica ──────────────────────────────
CREATE INDEX IF NOT EXISTS idx_empresa_depto ON empresa(departamento);
CREATE INDEX IF NOT EXISTS idx_empresa_turista ON empresa(modo_turista);
CREATE INDEX IF NOT EXISTS idx_empresa_created ON empresa(created_at);
