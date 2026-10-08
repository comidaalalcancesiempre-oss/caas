-- ══════════════════════════════════════════════════════════════════
-- C.A.A.S. - Comida Al Alcance Siempre
-- Script SQL de la base de datos
--
-- Cómo importar: phpMyAdmin → pestaña Importar → elegir este archivo
-- ══════════════════════════════════════════════════════════════════

-- Crear la base de datos si no existe, con soporte de emojis y tildes (utf8mb4)
CREATE DATABASE IF NOT EXISTS caas_v2
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Usar esta base de datos para todas las tablas de abajo
USE caas_v2;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: usuario
-- Es la "tabla padre" de la jerarquía IS-A del MER
-- Guarda los datos comunes a todos los tipos de usuario
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE usuario (
    id_usuario   INT UNSIGNED NOT NULL AUTO_INCREMENT, -- ID único, se asigna solo (1, 2, 3...)
    email        VARCHAR(150) NOT NULL,                -- email único por usuario
    telefono     VARCHAR(20)  NOT NULL,                -- teléfono/WhatsApp obligatorio
    tipo         ENUM('CLIENTE','EMPRESA','ADMIN') NOT NULL DEFAULT 'CLIENTE', -- lista cerrada de roles
    PRIMARY KEY (id_usuario),                          -- clave primaria: identifica cada fila
    UNIQUE KEY uk_email (email)                        -- no puede haber dos usuarios con el mismo email
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: cliente
-- Hereda de usuario (IS-A) — solo clientes tienen dirección de entrega
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE cliente (
    id_cliente  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario  INT UNSIGNED NOT NULL,                 -- referencia al usuario padre
    nombre      VARCHAR(100) NOT NULL,
    contrasena  VARCHAR(255) NOT NULL,                 -- se guarda el HASH, nunca la contraseña real
    calle       VARCHAR(100) NOT NULL DEFAULT '',
    num_casa    VARCHAR(20)  NOT NULL DEFAULT '',
    PRIMARY KEY (id_cliente),
    UNIQUE KEY uk_usuario_cliente (id_usuario),        -- un usuario solo puede tener un cliente
    -- FOREIGN KEY: el id_usuario debe existir en la tabla usuario
    -- ON DELETE CASCADE: si se borra el usuario, se borra el cliente automáticamente
    CONSTRAINT fk_cliente_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: empresa
-- Hereda de usuario (IS-A) — solo empresas tienen categoría y estado de aprobación
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE empresa (
    id_empresa        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario        INT UNSIGNED NOT NULL,
    nombre            VARCHAR(100) NOT NULL,
    contrasena        VARCHAR(255) NOT NULL,           -- hash bcrypt, nunca texto plano
    categoria         VARCHAR(80)  NOT NULL DEFAULT 'General',
    direccion         VARCHAR(255) NOT NULL DEFAULT '',
    horarios          VARCHAR(100) NOT NULL DEFAULT '',
    logo              VARCHAR(255) NOT NULL DEFAULT 'default_logo.png', -- nombre del archivo subido
    -- ENUM: solo acepta estos tres valores, cualquier otro MySQL lo rechaza
    -- DEFAULT 'PENDIENTE': toda empresa nueva entra pendiente hasta que el admin la apruebe
    estado_aprobacion ENUM('PENDIENTE','APROBADO','RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
    PRIMARY KEY (id_empresa),
    UNIQUE KEY uk_usuario_empresa (id_usuario),
    CONSTRAINT fk_empresa_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: menu
-- Relación 1 a N: una empresa tiene uno o varios menúes
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE menu (
    id_menu    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_empresa INT UNSIGNED NOT NULL,                  -- a qué empresa pertenece este menú
    nombre     VARCHAR(100) NOT NULL DEFAULT 'Menú Principal',
    PRIMARY KEY (id_menu),
    CONSTRAINT fk_menu_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresa(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: producto
-- Los platos que publica cada empresa
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE producto (
    id_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_empresa  INT UNSIGNED NOT NULL,                 -- a qué empresa pertenece
    nombre      VARCHAR(150) NOT NULL,
    precio      DECIMAL(10,2) NOT NULL DEFAULT 0.00,   -- DECIMAL para evitar errores de punto flotante
    descripcion TEXT,                                  -- texto largo para ingredientes
    imagen      VARCHAR(255) NOT NULL DEFAULT 'default.jpg', -- nombre del archivo en /uploads
    PRIMARY KEY (id_producto),
    CONSTRAINT fk_producto_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresa(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: menu_producto
-- Relación N a N entre menú y producto
-- Un menú tiene muchos productos, un producto puede estar en muchos menúes
-- Esta tabla intermedia resuelve la relación muchos a muchos del MER
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE menu_producto (
    id_menu     INT UNSIGNED NOT NULL,
    id_producto INT UNSIGNED NOT NULL,
    -- Clave primaria compuesta: la combinación de los dos IDs debe ser única
    -- Evita que el mismo producto aparezca dos veces en el mismo menú
    PRIMARY KEY (id_menu, id_producto),
    CONSTRAINT fk_mp_menu    FOREIGN KEY (id_menu)
        REFERENCES menu(id_menu)       ON DELETE CASCADE,
    CONSTRAINT fk_mp_product FOREIGN KEY (id_producto)
        REFERENCES producto(id_producto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────────────────────────
-- TABLA: pedido
-- Relación 1 a N: un cliente puede hacer muchos pedidos
-- Registra cada pedido con su estado actual
-- ──────────────────────────────────────────────────────────────────
CREATE TABLE pedido (
    id_pedido   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_cliente  INT UNSIGNED NOT NULL,                 -- quién hizo el pedido
    id_empresa  INT UNSIGNED NOT NULL,                 -- a qué empresa le pidió
    detalle     TEXT         NOT NULL,                 -- descripción del pedido (nombre + notas)
    costo       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    -- Estado del pedido: sigue el flujo Pendiente → preparación → camino → Entregado
    estado      ENUM('Pendiente','En preparación','En camino','Entregado','Cancelado')
                NOT NULL DEFAULT 'Pendiente',
    -- DEFAULT CURRENT_TIMESTAMP: se guarda automáticamente la fecha y hora del pedido
    fecha_hora  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_pedido),
    CONSTRAINT fk_pedido_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
    CONSTRAINT fk_pedido_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════════════════════════
-- DATOS DE DEMOSTRACIÓN
-- Empresas y usuarios de prueba para mostrar el sistema funcionando
-- Las contraseñas se hashean ejecutando init_passwords.php después
-- ══════════════════════════════════════════════════════════════════

-- Admin del sistema
INSERT INTO usuario (email, telefono, tipo) VALUES ('admin@caas.com', '000000000', 'ADMIN');
INSERT INTO cliente (id_usuario, nombre, contrasena, calle, num_casa) VALUES
    (1, 'Administrador', '$2y$12$placeholder', '', '');

-- Empresa 1: aprobada
INSERT INTO usuario (email, telefono, tipo) VALUES ('gueyaburger@caas.com', '098111222', 'EMPRESA');
INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, estado_aprobacion) VALUES
    (2, 'La Güeya Burger', '$2y$12$placeholder', 'Hamburguesería', 'Av. 8 de Octubre 1234', '19:00 - 23:30', 'APROBADO');
INSERT INTO menu (id_empresa, nombre) VALUES (1, 'Menú Principal');

-- Empresa 2: aprobada
INSERT INTO usuario (email, telefono, tipo) VALUES ('donpancho@caas.com', '098333444', 'EMPRESA');
INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, estado_aprobacion) VALUES
    (3, 'Don Pancho Pizza', '$2y$12$placeholder', 'Pizzería', 'Br. Artigas 567', '18:00 - 00:00', 'APROBADO');
INSERT INTO menu (id_empresa, nombre) VALUES (2, 'Menú Principal');

-- Empresa 3: pendiente (para demostrar el flujo de aprobación)
INSERT INTO usuario (email, telefono, tipo) VALUES ('asadojorge@caas.com', '098555666', 'EMPRESA');
INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, estado_aprobacion) VALUES
    (4, 'El Asado de Jorge', '$2y$12$placeholder', 'Parrillada', 'Calle Principal 890', '12:00 - 15:00', 'PENDIENTE');
INSERT INTO menu (id_empresa, nombre) VALUES (3, 'Menú Principal');

-- Cliente de prueba
INSERT INTO usuario (email, telefono, tipo) VALUES ('cliente@caas.com', '099111222', 'CLIENTE');
INSERT INTO cliente (id_usuario, nombre, contrasena, calle, num_casa) VALUES
    (5, 'Juan Pérez', '$2y$12$placeholder', 'Av. Italia', '4321');
