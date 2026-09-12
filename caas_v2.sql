-- ══════════════════════════════════════════════════════════════════
-- C.A.A.S. - Comida Al Alcance Siempre
-- Script SQL completo - Base de datos: caas_v2
-- Estructura basada en el MER del proyecto
--
-- INSTRUCCIONES:
-- 1. Abrir phpMyAdmin → pestaña SQL (sin seleccionar ninguna BD)
-- 2. Pegar todo este contenido y ejecutar
-- ══════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS caas_v2
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE caas_v2;

-- ──────────────────────────────────────────────────────────────────
-- TABLA RAÍZ: usuario
-- Entidad padre de la jerarquía IS-A
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE usuario (
    id_usuario   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email        VARCHAR(150) NOT NULL,
    telefono     VARCHAR(20)  NOT NULL,
    tipo         ENUM('CLIENTE','EMPRESA','ADMIN') NOT NULL DEFAULT 'CLIENTE',
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uk_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: cliente (IS-A usuario)
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE cliente (
    id_cliente  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario  INT UNSIGNED NOT NULL,
    nombre      VARCHAR(100) NOT NULL,
    contrasena  VARCHAR(255) NOT NULL,
    calle       VARCHAR(100) NOT NULL DEFAULT '',
    num_casa    VARCHAR(20)  NOT NULL DEFAULT '',
    PRIMARY KEY (id_cliente),
    UNIQUE KEY uk_usuario_cliente (id_usuario),
    CONSTRAINT fk_cliente_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: empresa (IS-A usuario)
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE empresa (
    id_empresa        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario        INT UNSIGNED NOT NULL,
    nombre            VARCHAR(100) NOT NULL,
    contrasena        VARCHAR(255) NOT NULL,
    categoria         VARCHAR(80)  NOT NULL DEFAULT 'General',
    direccion         VARCHAR(255) NOT NULL DEFAULT '',
    horarios          VARCHAR(100) NOT NULL DEFAULT '',
    logo              VARCHAR(255) NOT NULL DEFAULT 'default_logo.png',
    estado_aprobacion ENUM('PENDIENTE','APROBADO','RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
    PRIMARY KEY (id_empresa),
    UNIQUE KEY uk_usuario_empresa (id_usuario),
    CONSTRAINT fk_empresa_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: menu (una empresa posee 1..N menúes)
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE menu (
    id_menu    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_empresa INT UNSIGNED NOT NULL,
    nombre     VARCHAR(100) NOT NULL DEFAULT 'Menú Principal',
    PRIMARY KEY (id_menu),
    CONSTRAINT fk_menu_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresa(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: producto
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE producto (
    id_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_empresa  INT UNSIGNED NOT NULL,
    nombre      VARCHAR(150) NOT NULL,
    precio      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descripcion TEXT,
    imagen      VARCHAR(255) NOT NULL DEFAULT 'default.jpg',
    PRIMARY KEY (id_producto),
    CONSTRAINT fk_producto_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresa(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA RELACIÓN N:N: menu_producto
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE menu_producto (
    id_menu     INT UNSIGNED NOT NULL,
    id_producto INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_menu, id_producto),
    CONSTRAINT fk_mp_menu    FOREIGN KEY (id_menu)     REFERENCES menu(id_menu)       ON DELETE CASCADE,
    CONSTRAINT fk_mp_product FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: pedido (un cliente encarga 1..N pedidos)
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE pedido (
    id_pedido   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_cliente  INT UNSIGNED NOT NULL,
    id_empresa  INT UNSIGNED NOT NULL,
    detalle     TEXT         NOT NULL,
    costo       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado      ENUM('Pendiente','En preparación','En camino','Entregado','Cancelado')
                NOT NULL DEFAULT 'Pendiente',
    fecha_hora  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pedido),
    CONSTRAINT fk_pedido_cliente  FOREIGN KEY (id_cliente)  REFERENCES cliente(id_cliente),
    CONSTRAINT fk_pedido_empresa  FOREIGN KEY (id_empresa)  REFERENCES empresa(id_empresa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════════
-- DATOS INICIALES DE DEMOSTRACIÓN
-- ══════════════════════════════════════════════════════════════════

-- ── Admin ─────────────────────────────────────────────────────────
-- Contraseña: admin1234
INSERT INTO usuario (email, telefono, tipo) VALUES
    ('admin@caas.com', '000000000', 'ADMIN');

INSERT INTO cliente (id_usuario, nombre, contrasena, calle, num_casa) VALUES
    (1, 'Administrador', '$2y$12$YKkzGmRzKpJQ5bPXM1P7.OLjTqzB5TkHPLn2LsJxH8GMuLJiD5Rmi', '', '');

-- ── Empresa 1: La Güeya Burger ────────────────────────────────────
-- Contraseña: burger123
INSERT INTO usuario (email, telefono, tipo) VALUES
    ('gueyaburger@caas.com', '098111222', 'EMPRESA');

INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, estado_aprobacion) VALUES
    (2, 'La Güeya Burger', '$2y$12$N.IQkzL8oXjGkZpKqP3iYu4YnN6M2qR5tW7vX1sA9bC0dE2fG3hH4', 
     'Hamburguesería', 'Av. 8 de Octubre 1234', '19:00 - 23:30', 'APROBADO');

INSERT INTO menu (id_empresa, nombre) VALUES (1, 'Menú Principal');

-- ── Empresa 2: Don Pancho Pizza ──────────────────────────────────
-- Contraseña: pizza123
INSERT INTO usuario (email, telefono, tipo) VALUES
    ('donpancho@caas.com', '098333444', 'EMPRESA');

INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, estado_aprobacion) VALUES
    (3, 'Don Pancho Pizza', '$2y$12$N.IQkzL8oXjGkZpKqP3iYu4YnN6M2qR5tW7vX1sA9bC0dE2fG3hH4',
     'Pizzería', 'Br. Artigas 567', '18:00 - 00:00', 'APROBADO');

INSERT INTO menu (id_empresa, nombre) VALUES (2, 'Menú Principal');

-- ── Empresa 3: El Asado de Jorge (pendiente) ──────────────────────
-- Contraseña: asado123
INSERT INTO usuario (email, telefono, tipo) VALUES
    ('asadojorge@caas.com', '098555666', 'EMPRESA');

INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, estado_aprobacion) VALUES
    (4, 'El Asado de Jorge', '$2y$12$N.IQkzL8oXjGkZpKqP3iYu4YnN6M2qR5tW7vX1sA9bC0dE2fG3hH4',
     'Parrillada', 'Calle Principal 890', '12:00 - 15:00', 'PENDIENTE');

INSERT INTO menu (id_empresa, nombre) VALUES (3, 'Menú Principal');

-- ── Cliente de prueba ─────────────────────────────────────────────
-- Contraseña: cliente123
INSERT INTO usuario (email, telefono, tipo) VALUES
    ('cliente@caas.com', '099111222', 'CLIENTE');

INSERT INTO cliente (id_usuario, nombre, contrasena, calle, num_casa) VALUES
    (5, 'Juan Pérez', '$2y$12$ABC123defGHI456jklMNO789pqrSTU012vwxYZab345cdeFGH678ij9',
     'Av. Italia', '4321');

-- ══════════════════════════════════════════════════════════════════
-- NOTA SOBRE CONTRASEÑAS DE DEMOSTRACIÓN
-- Los hashes de arriba son de ejemplo y probablemente no funcionen
-- directamente. Para generar hashes reales corré init_passwords.php
-- después de importar este SQL.
-- ══════════════════════════════════════════════════════════════════
