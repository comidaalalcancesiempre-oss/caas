-- ══════════════════════════════════════════════════════════════════
-- C.A.A.S. — Datos de demostración completos
-- Ejecutar en phpMyAdmin → caas_v2 → SQL
-- Todas las contraseñas son: Demo1234!
-- ══════════════════════════════════════════════════════════════════
USE caas_v2;

-- ──────────────────────────────────────────────────────────────────
-- LIMPIAR datos previos de demo (mantiene estructura)
-- ──────────────────────────────────────────────────────────────────
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM menu_producto;
DELETE FROM menu;
DELETE FROM producto;
DELETE FROM pedido;
DELETE FROM empresa_stats;
DELETE FROM empresa WHERE id_empresa > 0;
DELETE FROM cliente WHERE id_cliente > 0;
DELETE FROM usuario WHERE id_usuario > 0;
SET FOREIGN_KEY_CHECKS = 1;
ALTER TABLE usuario AUTO_INCREMENT = 1;
ALTER TABLE cliente AUTO_INCREMENT = 1;
ALTER TABLE empresa AUTO_INCREMENT = 1;
ALTER TABLE menu AUTO_INCREMENT = 1;
ALTER TABLE producto AUTO_INCREMENT = 1;
ALTER TABLE pedido AUTO_INCREMENT = 1;

-- ══════════════════════════════════════════════════════════════════
-- USUARIOS — contraseña para todos: Demo1234!
-- Hash bcrypt generado con password_hash('Demo1234!', PASSWORD_BCRYPT, ['cost'=>12])
-- ══════════════════════════════════════════════════════════════════

-- ── 1 ADMIN ───────────────────────────────────────────────────────
INSERT INTO usuario (email, telefono, tipo, departamento, localidad) VALUES
('admin@caas.com.uy', '099000001', 'ADMIN', 'Montevideo', 'Montevideo');
INSERT INTO cliente (id_usuario, nombre, contrasena, calle, num_casa) VALUES
(1, 'Admin C.A.A.S.', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '', '');

-- ── 10 CLIENTES ───────────────────────────────────────────────────
INSERT INTO usuario (email, telefono, tipo, departamento, localidad) VALUES
('sofia.garcia@gmail.com',   '099111101', 'CLIENTE', 'Montevideo',  'Pocitos'),
('martin.perez@gmail.com',   '099111102', 'CLIENTE', 'Montevideo',  'Cordón'),
('valentina.lopez@gmail.com','099111103', 'CLIENTE', 'Canelones',   'Las Piedras'),
('lucas.rodriguez@gmail.com','099111104', 'CLIENTE', 'Maldonado',   'Punta del Este'),
('camila.diaz@gmail.com',    '099111105', 'CLIENTE', 'Colonia',     'Colonia del Sacramento'),
('nicolas.fernandez@gmail.com','099111106','CLIENTE', 'Salto',       'Salto'),
('florencia.martinez@gmail.com','099111107','CLIENTE','Montevideo',  'Carrasco'),
('andres.gonzalez@gmail.com','099111108', 'CLIENTE', 'Paysandú',    'Paysandú'),
('isabella.sanchez@gmail.com','099111109', 'CLIENTE', 'Rocha',       'La Paloma'),
('mateo.romero@gmail.com',   '099111110', 'CLIENTE', 'Montevideo',  'Centro');

INSERT INTO cliente (id_usuario, nombre, contrasena, calle, num_casa) VALUES
(2,  'Sofía García',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Av. Brasil',          '1245'),
(3,  'Martín Pérez',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Av. 18 de Julio',     '890'),
(4,  'Valentina López',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Calle Rivera',        '234'),
(5,  'Lucas Rodríguez',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Av. Roosevelt',       '567'),
(6,  'Camila Díaz',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Calle General Flores','12'),
(7,  'Nicolás Fernández',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Artigas',             '678'),
(8,  'Florencia Martínez',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Av. Bolivia',         '3210'),
(9,  'Andrés González',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Leandro Gómez',       '445'),
(10, 'Isabella Sánchez',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Calle del Barrio',    '7'),
(11, 'Mateo Romero',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tristán Narvaja',     '1001');

-- ── 10 EMPRESAS ───────────────────────────────────────────────────
INSERT INTO usuario (email, telefono, tipo, departamento, localidad) VALUES
('gueyaburger@caas.com.uy',     '098201001', 'EMPRESA', 'Montevideo', 'Pocitos'),
('lapizzeriaderoma@caas.com.uy','098201002', 'EMPRESA', 'Montevideo', 'Centro'),
('elasadodejorge@caas.com.uy',  '098201003', 'EMPRESA', 'Maldonado',  'Punta del Este'),
('sushiuy@caas.com.uy',         '098201004', 'EMPRESA', 'Montevideo', 'Punta Carretas'),
('donpanchopizza@caas.com.uy',  '098201005', 'EMPRESA', 'Canelones',  'Las Piedras'),
('laparrilladecolonia@caas.com.uy','098201006','EMPRESA','Colonia',   'Colonia del Sacramento'),
('tacosmexico@caas.com.uy',     '098201007', 'EMPRESA', 'Montevideo', 'Cordón'),
('submarinosaraudi@caas.com.uy','098201008', 'EMPRESA', 'Salto',      'Salto'),
('veganitouy@caas.com.uy',      '098201009', 'EMPRESA', 'Montevideo', 'Buceo'),
('dulcerialaabuela@caas.com.uy','098201010', 'EMPRESA', 'Rocha',      'La Paloma');

INSERT INTO empresa (id_usuario, nombre, contrasena, categoria, direccion, horarios, departamento, localidad, modo_turista, plato_tipico, estado_aprobacion, latitud, longitud, descripcion, created_at) VALUES
(12, 'La Güeya Burger',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Hamburguesería', 'Av. Brasil 1234, Pocitos', '19:00 - 23:30',
 'Montevideo', 'Pocitos', 0, NULL, 'APROBADO',
 -34.9100, -56.1600,
 'Las mejores hamburguesas artesanales de Montevideo. Carne 100% uruguaya, panes caseros y salsas secretas.', NOW() - INTERVAL 30 DAY),

(13, 'La Pizzería de Roma',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Pizzería', 'Av. 18 de Julio 987, Centro', '18:00 - 00:00',
 'Montevideo', 'Centro', 1, 'Pizza a la leña estilo napolitano', 'APROBADO',
 -34.9058, -56.1913,
 'Auténtica pizza italiana horneada en horno de leña. Ingredientes importados directamente de Italia.', NOW() - INTERVAL 25 DAY),

(14, 'El Asado de Jorge',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Parrillada', 'Rambla Lorenzo Batlle 456, Punta del Este', '12:00 - 16:00 y 20:00 - 00:00',
 'Maldonado', 'Punta del Este', 1, 'Asado de tira y chorizos uruguayos', 'APROBADO',
 -34.9680, -54.9402,
 'La mejor parrillada de Punta del Este con vista al mar. Carnes seleccionadas de estancias uruguayas.', NOW() - INTERVAL 20 DAY),

(15, 'Sushi UY',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Japonesa', 'Ellauri 555, Punta Carretas', '20:00 - 00:00',
 'Montevideo', 'Punta Carretas', 1, 'Rolls de pescado fresco del Río de la Plata', 'APROBADO',
 -34.9200, -56.1550,
 'Sushi premium con pescados frescos del día. Fusión japonesa con toques uruguayos.', NOW() - INTERVAL 15 DAY),

(16, 'Don Pancho Pizza',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Pizzería', 'Br. Artigas 567, Las Piedras', '18:00 - 23:00',
 'Canelones', 'Las Piedras', 0, NULL, 'APROBADO',
 -34.7300, -56.2200,
 'Pizza familiar al estilo canario. Porciones generosas y precios accesibles para toda la familia.', NOW() - INTERVAL 10 DAY),

(17, 'La Parrilla de Colonia',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Parrillada', 'Calle General Flores 234', '12:00 - 15:30 y 19:30 - 23:00',
 'Colonia', 'Colonia del Sacramento', 1, 'Cordero a las brasas y bodegones coloniales', 'APROBADO',
 -34.4620, -57.8480,
 'En el corazón del casco histórico de Colonia. Carnes al fuego con vistas coloniales. Ideal para turistas.', NOW() - INTERVAL 8 DAY),

(18, 'Tacos México UY',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Mexicana', 'Dr. Tristán Narvaja 890, Cordón', '12:00 - 15:00 y 19:00 - 23:30',
 'Montevideo', 'Cordón', 0, NULL, 'APROBADO',
 -34.9050, -56.1800,
 'Sabores auténticos de México en Montevideo. Tacos, burritos, quesadillas y margaritas.', NOW() - INTERVAL 5 DAY),

(19, 'Submarinos Araudi',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Sandwichería', 'Artigas 1100, Salto', '11:00 - 22:00',
 'Salto', 'Salto', 0, NULL, 'APROBADO',
 -31.3833, -57.9667,
 'Los mejores submarinos y sandwiches del norte del país. Rápido, rico y al paso.', NOW() - INTERVAL 3 DAY),

(20, 'Veganito UY',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Vegana', 'Av. Bolivia 2345, Buceo', '09:00 - 21:00',
 'Montevideo', 'Buceo', 0, NULL, 'APROBADO',
 -34.8950, -56.1400,
 '100% plant-based. Opciones deliciosas para veganos y vegetarianos. Sin gluten disponible.', NOW() - INTERVAL 2 DAY),

(21, 'Dulcería La Abuela',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Pastelería', 'Calle del Turista 12, La Paloma', '08:00 - 20:00',
 'Rocha', 'La Paloma', 1, 'Alfajores de maicena y tortas fritas', 'APROBADO',
 -34.6500, -54.1500,
 'Pastelería artesanal frente al mar en La Paloma. Recetas de la abuela con ingredientes naturales.', NOW() - INTERVAL 1 DAY);

-- ── Menús principales ─────────────────────────────────────────────
INSERT INTO menu (id_empresa, nombre) VALUES
(1,'Menú Principal'),(2,'Menú Principal'),(3,'Menú Principal'),
(4,'Menú Principal'),(5,'Menú Principal'),(6,'Menú Principal'),
(7,'Menú Principal'),(8,'Menú Principal'),(9,'Menú Principal'),(10,'Menú Principal');

-- ══════════════════════════════════════════════════════════════════
-- PRODUCTOS
-- Imágenes de placeholder temáticas (Unsplash público)
-- ══════════════════════════════════════════════════════════════════

-- ── La Güeya Burger (id_empresa=1) ────────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(1, 'Clásica Güeya',         320.00, 'Medallón de carne 200g, lechuga, tomate, cebolla caramelizada y salsa especial de la casa.', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400'),
(1, 'Doble Fuego',           420.00, 'Doble medallón, doble queso cheddar, panceta crocante y salsa BBQ ahumada.', 'https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=400'),
(1, 'Veggie Power',          290.00, 'Medallón de lentejas y quinoa, rúcula, tomate cherry, aguacate y aderezo de limón.', 'https://images.unsplash.com/photo-1525059696034-4967a8e1dca2?w=400'),
(1, 'Papas Fritas Rústicas', 150.00, 'Papas con cáscara cortadas en gajos, sazonadas con chimichurri y sal marina.', 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=400'),
(1, 'Chicken Crispy',        350.00, 'Pechuga de pollo empanada crujiente, coleslaw casero, pepinillos y mayonesa de miel.', 'https://images.unsplash.com/photo-1606755962773-d324e0a13086?w=400'),
(1, 'Combo Familiar',        890.00, '3 hamburguesas clásicas + papas grandes + 3 refrescos. Ideal para compartir.', 'https://images.unsplash.com/photo-1561758033-7e924f619b47?w=400');

-- ── La Pizzería de Roma (id_empresa=2) ────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(2, 'Margherita DOC',        450.00, 'Salsa de tomate San Marzano, mozzarella di bufala, albahaca fresca. La clásica italiana.', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=400'),
(2, 'Cuatro Quesos',         520.00, 'Mozzarella, gorgonzola, parmesano y brie sobre base de tomate artesanal.', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400'),
(2, 'Prosciutto e Rucola',   580.00, 'Jamón crudo italiano, rúcula fresca, parmesano en escamas y aceite de oliva extra virgen.', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400'),
(2, 'Diavola Picante',       490.00, 'Salame picante, pimientos asados, aceitunas negras y jalapeños. Para los valientes.', 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=400'),
(2, 'Calzone Romano',        510.00, 'Pizza doblada rellena de ricota, espinaca, mozzarella y jamón cocido.', 'https://images.unsplash.com/photo-1571407970349-bc81e7e96d47?w=400'),
(2, 'Tiramisú',              220.00, 'Postre italiano clásico. Bizcochuelo, café, mascarpone y cacao amargo. Receta original.', 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=400');

-- ── El Asado de Jorge (id_empresa=3) ──────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(3, 'Asado de Tira Premium', 890.00, 'Kilo de asado de tira de novillo seleccionado, cocción lenta al fuego de leña. Incluye chimichurri casero.', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400'),
(3, 'Chorizo Criollo',       280.00, 'Chorizo artesanal de cerdo y res, acompañado de pan casero y salsa criolla.', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400'),
(3, 'Entraña con Chimichurri',750.00, 'Entraña de novillo tierna y jugosa, punto de cocción a elección. Chimichurri de la casa.', 'https://images.unsplash.com/photo-1558030006-450675393462?w=400'),
(3, 'Tabla Parrillera Familiar',2200.00,'Para 4 personas: asado de tira, vacío, chorizo, morcilla y pollo. El festín uruguayo completo.', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400'),
(3, 'Provoleta a las Brasas', 320.00, 'Queso provolone grillado con orégano y ají molido. Entrada ideal para el asado.', 'https://images.unsplash.com/photo-1536964549204-cce9eab227bd?w=400'),
(3, 'Vacío al Disco',        820.00, 'Vacío cocido lentamente en disco de arado con verduras de estación y hierbas aromáticas.', 'https://images.unsplash.com/photo-1529042410759-befb1204b468?w=400');

-- ── Sushi UY (id_empresa=4) ───────────────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(4, 'Roll Plata',            480.00, '8 piezas de roll relleno con camarón, pepino y palta, cubierto con salmón y salsa ponzu.', 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=400'),
(4, 'Nigiri Salmón x6',      420.00, '6 nigiris de salmón fresco del Atlántico sobre arroz de sushi premium. Wasabi y jengibre.', 'https://images.unsplash.com/photo-1617196034738-26c5f7c977ce?w=400'),
(4, 'Sashimi Mix 12pz',      650.00, '12 láminas de sashimi variado: salmón, atún y lenguado. Presentación japonesa tradicional.', 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=400'),
(4, 'Roll Tempura Ebi',      510.00, 'Camarón en tempura crujiente, pepino, palta y mayonesa japonesa. 8 piezas.', 'https://images.unsplash.com/photo-1559410545-0bdcd187e0a6?w=400'),
(4, 'Ramen de Cerdo',        490.00, 'Caldo tonkotsu 12 horas, chashu de cerdo, huevo marinado, nori y cebollín.', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400'),
(4, 'Gyoza x6',              280.00, 'Dumplings de cerdo y repollo, fritos en plancha. Salsa de soja y vinagre de arroz.', 'https://images.unsplash.com/photo-1496116218417-1a781b1c416c?w=400');

-- ── Don Pancho Pizza (id_empresa=5) ───────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(5, 'Muzarella Grande',      380.00, 'Pizza de mozzarella generosa con orégano y aceite de oliva. El clásico de siempre.', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=400'),
(5, 'Fugazzeta Rellena',     450.00, 'Pizza de cebolla con doble capa de queso y jamón. Receta canaria auténtica.', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400'),
(5, 'Napolitana',            400.00, 'Tomate en rodajas, mozzarella, ajo y albahaca. Simple y deliciosa.', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400'),
(5, 'Media Docena Empanadas',320.00, '6 empanadas variadas: carne, pollo y jamón y queso. Recetas caseras de siempre.', 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400'),
(5, 'Coca de Jamón',         350.00, 'Masa fina crocante con jamón cocido, pimientos y queso. Especialidad de la casa.', 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=400');

-- ── La Parrilla de Colonia (id_empresa=6) ─────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(6, 'Cordero a las Brasas',  1200.00,'Pierna de cordero patagónico cocinada lentamente. Plato típico colonial. Para 2 personas.', 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=400'),
(6, 'Picada Colonial',        680.00, 'Tabla con fiambres artesanales, quesos del campo, aceitunas, pan casero y encurtidos.', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400'),
(6, 'Trucha del Río',         750.00, 'Trucha fresca del río Uruguay a la parrilla con hierbas del jardín y papas rústicas.', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=400'),
(6, 'Bife de Lomo',           950.00, 'Lomo de 300g punto a elección, con guarnición de puré rústico y ensalada verde.', 'https://images.unsplash.com/photo-1558030006-450675393462?w=400'),
(6, 'Postre Colonial',        220.00, 'Dulce de leche casero con quesillo fresco del campo. La combinación perfecta uruguaya.', 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400');

-- ── Tacos México UY (id_empresa=7) ────────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(7, 'Tacos al Pastor x3',    380.00, '3 tacos de cerdo marinado en achiote, piña, cilantro y cebolla. Salsas verde y roja.', 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=400'),
(7, 'Burrito Carnitas',       450.00, 'Tortilla grande rellena de cerdo desmenuzado, frijoles, arroz, guacamole y crema.', 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=400'),
(7, 'Quesadillas Pollo',      350.00, 'Tortillas de maíz con pollo grillado, queso Oaxaca, pimientos y cebolla morada.', 'https://images.unsplash.com/photo-1600891964599-f61ba0e24092?w=400'),
(7, 'Guacamole Artesanal',    180.00, 'Aguacate Hass, limón, cilantro, tomate y jalapeño. Servido con totopos de maíz.', 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=400'),
(7, 'Nachos Supremos',        420.00, 'Totopos con queso fundido, jalapeños, guacamole, crema agria y salsa roja.', 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?w=400'),
(7, 'Margarita Clásica',      220.00, 'Tequila blanco, triple sec, limón fresco y sal. Con o sin alcohol.', 'https://images.unsplash.com/photo-1556855810-ac404aa91e85?w=400');

-- ── Submarinos Araudi (id_empresa=8) ──────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(8, 'Submarino Clásico',      280.00, 'Pan francés, jamón cocido, queso, lechuga, tomate, mayonesa. El rey de los sandwiches.', 'https://images.unsplash.com/photo-1553909489-cd47e0907980?w=400'),
(8, 'Chivito Completo',       420.00, 'El plato nacional uruguayo: lomo, jamón, panceta, huevo, queso y ensalada. Icónico.', 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=400'),
(8, 'Pancho con Todo',        220.00, 'Salchicha en pan, mostaza, ketchup, mayonesa, chimichurri y papitas fritas.', 'https://images.unsplash.com/photo-1648025570691-d73b3ab34d3e?w=400'),
(8, 'Milanesa Napolitana',    380.00, 'Milanesa de ternera con salsa de tomate, jamón y queso gratinado. Con papas fritas.', 'https://images.unsplash.com/photo-1529042410759-befb1204b468?w=400'),
(8, 'Empanadas Araudi x4',    260.00, '4 empanadas de carne cortada a cuchillo, con aceitunas, huevo y pasas. Receta norteña.', 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400');

-- ── Veganito UY (id_empresa=9) ────────────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(9, 'Bowl Verde Power',       380.00, 'Quinoa, espinaca, brócoli, garbanzos especiados, aguacate y aderezo tahini-limón.', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400'),
(9, 'Burger Vegana',          350.00, 'Medallón de remolacha y legumbres, lechuga, tomate, cebolla y aioli de ajo negro.', 'https://images.unsplash.com/photo-1525059696034-4967a8e1dca2?w=400'),
(9, 'Wrap de Falafel',        320.00, 'Wrap integral con falafel casero, hummus, pepino, tomate cherry y salsa de yogur vegano.', 'https://images.unsplash.com/photo-1540713434306-58505cf1b6fc?w=400'),
(9, 'Smoothie Tropical',      180.00, 'Mango, piña, banana, leche de coco y jengibre. 100% natural, sin azúcar añadida.', 'https://images.unsplash.com/photo-1505252585461-04db1eb84625?w=400'),
(9, 'Cheesecake Vegano',      220.00, 'Base de dátiles y nueces, relleno de anacardos con frutos rojos. Sin lácteos ni huevos.', 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=400'),
(9, 'Desayuno Saludable',     290.00, 'Granola artesanal, yogur de coco, frutas de estación y miel de abeja local.', 'https://images.unsplash.com/photo-1484723091739-30a097e8f929?w=400');

-- ── Dulcería La Abuela (id_empresa=10) ────────────────────────────
INSERT INTO producto (id_empresa, nombre, precio, descripcion, imagen) VALUES
(10, 'Alfajores x6',          320.00, '6 alfajores de maicena rellenos con dulce de leche y bañados en chocolate blanco y negro.', 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=400'),
(10, 'Torta de Playa',        580.00, 'Torta húmeda de chocolate con dulce de leche, decorada con coco rallado. Para 6 personas.', 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400'),
(10, 'Budín de Limón',        280.00, 'Budín casero con ralladura de limón fresco y glasé. Receta de la abuela de La Paloma.', 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=400'),
(10, 'Tortas Fritas x5',      150.00, '5 tortas fritas crujientes con azúcar impalpable. El snack playero más querido de Uruguay.', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400'),
(10, 'Medialuna de Manteca',   80.00, 'Medialuna hojaldrada de manteca. Perfecta para el desayuno o la merienda playera.', 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=400'),
(10, 'Pasta Frola',           250.00, 'Tarta de dulce de membrillo en masa quebrada. Clásico rioplatense hecho con amor.', 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=400');

-- ── Asociar productos a menús ─────────────────────────────────────
INSERT INTO menu_producto (id_menu, id_producto)
SELECT m.id_menu, p.id_producto
FROM menu m
JOIN producto p ON p.id_empresa = m.id_empresa;

-- ══════════════════════════════════════════════════════════════════
-- PEDIDOS de ejemplo (estados variados)
-- ══════════════════════════════════════════════════════════════════
INSERT INTO pedido (id_cliente, id_empresa, detalle, costo, estado, fecha_hora) VALUES
(1, 1, 'Clásica Güeya — Notas: sin pepinillos',              320.00, 'Entregado',      NOW() - INTERVAL 5 DAY),
(2, 2, 'Margherita DOC',                                     450.00, 'Entregado',      NOW() - INTERVAL 4 DAY),
(3, 3, 'Asado de Tira Premium',                              890.00, 'Entregado',      NOW() - INTERVAL 3 DAY),
(4, 4, 'Roll Plata — Notas: sin wasabi',                     480.00, 'Entregado',      NOW() - INTERVAL 2 DAY),
(5, 5, 'Muzarella Grande',                                   380.00, 'Entregado',      NOW() - INTERVAL 1 DAY),
(6, 6, 'Cordero a las Brasas — Notas: punto medio',         1200.00, 'Entregado',      NOW() - INTERVAL 6 HOUR),
(7, 7, 'Tacos al Pastor x3',                                 380.00, 'En camino',      NOW() - INTERVAL 30 MINUTE),
(8, 8, 'Chivito Completo',                                   420.00, 'En preparación', NOW() - INTERVAL 20 MINUTE),
(9, 9, 'Bowl Verde Power',                                   380.00, 'En preparación', NOW() - INTERVAL 15 MINUTE),
(10,10, 'Alfajores x6',                                      320.00, 'Pendiente',      NOW() - INTERVAL 5 MINUTE),
(1, 2, 'Cuatro Quesos',                                      520.00, 'Pendiente',      NOW() - INTERVAL 3 MINUTE),
(2, 1, 'Doble Fuego — Notas: extra panceta',                 420.00, 'Pendiente',      NOW() - INTERVAL 1 MINUTE),
(3, 7, 'Burrito Carnitas',                                   450.00, 'Pendiente',      NOW()),
(4, 9, 'Burger Vegana',                                      350.00, 'Pendiente',      NOW()),
(5, 4, 'Nigiri Salmón x6',                                   420.00, 'En preparación', NOW() - INTERVAL 10 MINUTE);

-- ── Inicializar/actualizar stats de empresas ──────────────────────
INSERT INTO empresa_stats (id_empresa, pedidos_totales, en_preparacion, en_camino, entregados, cancelados)
VALUES
(1, 2, 1, 0, 1, 0),(2, 2, 0, 0, 2, 0),(3, 1, 0, 0, 1, 0),(4, 2, 1, 0, 1, 0),
(5, 1, 0, 0, 1, 0),(6, 1, 0, 0, 1, 0),(7, 2, 0, 1, 0, 0),(8, 1, 1, 0, 0, 0),
(9, 2, 1, 0, 0, 0),(10,1, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE
    pedidos_totales = VALUES(pedidos_totales),
    en_preparacion  = VALUES(en_preparacion),
    en_camino       = VALUES(en_camino),
    entregados      = VALUES(entregados),
    cancelados      = VALUES(cancelados);

SELECT '✅ Datos de demo cargados correctamente' AS resultado;
