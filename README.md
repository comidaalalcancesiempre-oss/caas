# C.A.A.S. — Comida Al Alcance Siempre

> Proyecto Final — 3° Bachillerato Tecnológico en Informática

---

## ¿Qué es C.A.A.S.?

**C.A.A.S.** es una plataforma web centralizada y responsive que conecta a clientes locales con comercios gastronómicos del barrio. Permite consultar menúes, realizar pedidos y gestionar el estado de los mismos de forma ágil, sin necesidad de apps nativas ni instalaciones adicionales.

---

## Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.x |
| Base de Datos | MySQL (MariaDB) |
| Frontend | HTML5, CSS3, JavaScript |
| Framework CSS | Tailwind CSS (CDN) |
| Email | PHPMailer + Gmail SMTP |
| Entorno local | XAMPP (Apache + MySQL) |
| Control de versiones | Git / GitHub |

---

## Estructura del Proyecto

```
caas_nuevo/
│
├── config.php              # Conexión a BD y configuración global
├── login.php               # Inicio de sesión (cliente, empresa, admin)
├── registro.php            # Registro de nuevos usuarios
├── logout.php              # Cierre de sesión
│
├── index.php               # Catálogo público de locales y productos
├── local.php               # Página individual de cada local
│
├── empresa.php             # Panel de empresa: gestión de platos y pedidos
├── admin.php               # Panel de administrador: aprobación de empresas
│
├── realizar_pedido.php     # Endpoint AJAX: crear pedido
├── obtener_pedidos.php     # Endpoint AJAX: listar y actualizar pedidos
├── buscar_pedidos.php      # Endpoint AJAX: búsqueda de pedidos (admin)
│
├── mailer.php              # Sistema de notificaciones por email (PHPMailer)
├── setup_admin.php         # Registro inicial del administrador (uso único)
│
├── caas_v2.sql             # Script SQL completo de la base de datos
├── phpmailer/              # Librería PHPMailer
└── uploads/                # Imágenes subidas por las empresas
```

---

## Base de Datos — Modelo Relacional

### Diagrama de tablas

```
usuario
  ├── id_usuario (PK)
  ├── email
  ├── telefono
  └── tipo: CLIENTE | EMPRESA | ADMIN

      ├── cliente (IS-A usuario)
      │     ├── id_cliente (PK)
      │     ├── id_usuario (FK)
      │     ├── nombre
      │     ├── contrasena (hash bcrypt)
      │     ├── calle
      │     └── num_casa
      │
      └── empresa (IS-A usuario)
            ├── id_empresa (PK)
            ├── id_usuario (FK)
            ├── nombre
            ├── contrasena (hash bcrypt)
            ├── categoria
            ├── direccion
            ├── horarios
            ├── logo
            └── estado_aprobacion: PENDIENTE | APROBADO | RECHAZADO

empresa → menu (1:N)
  └── menu
        ├── id_menu (PK)
        ├── id_empresa (FK)
        └── nombre

menu ←→ producto (N:M)
  ├── producto
  │     ├── id_producto (PK)
  │     ├── id_empresa (FK)
  │     ├── nombre
  │     ├── precio
  │     ├── descripcion
  │     └── imagen
  │
  └── menu_producto (tabla intermedia)
        ├── id_menu (FK)
        └── id_producto (FK)

cliente → pedido (1:N)
  └── pedido
        ├── id_pedido (PK)
        ├── id_cliente (FK)
        ├── id_empresa (FK)
        ├── detalle
        ├── costo
        ├── estado: Pendiente | En preparación | En camino | Entregado | Cancelado
        └── fecha_hora
```

---

## Módulos del Sistema

### 1. Autenticación y Roles
- Registro de **Clientes** y **Empresas** con validación completa
- Login por email o teléfono con hashing de contraseñas (bcrypt)
- Sesiones seguras con regeneración de ID y cookies `httponly`
- Redirección automática según rol al iniciar sesión
- Las empresas quedan en estado **PENDIENTE** hasta ser aprobadas por el Admin

### 2. Catálogo Público
- Página principal con todos los locales activos y sus productos
- Buscador por nombre de local o plato
- Filtro por categoría (dinámico, se carga desde la BD)
- Responsive: se adapta a celulares, tablets y desktop

### 3. Pedidos
- Modal de pedido con campo de aclaraciones opcionales
- Registro del pedido en BD con estado "Pendiente"
- Redirección automática a **WhatsApp** del local con el detalle del pedido
- Solo pueden pedir usuarios con sesión de tipo CLIENTE

### 4. Panel Empresa
- Agregar, publicar y eliminar platos con imagen
- Visualización de pedidos recibidos con actualización automática cada 8 segundos
- Cambio de estado de pedidos: Pendiente → En preparación → En camino → Entregado

### 5. Panel Administrador
- Vista de todas las empresas registradas con su estado
- Aprobación o rechazo de empresas pendientes
- Tabla global de pedidos con búsqueda AJAX en tiempo real
- Cambio de estado de cualquier pedido desde el panel

---

## Instalación en XAMPP

### Requisitos
- XAMPP con Apache y MySQL activos
- PHP 8.0 o superior

### Pasos

**1. Clonar o copiar el proyecto**
```bash
git clone https://github.com/comidaalalcancesiempre-oss/caas.git
```
Copiar la carpeta a `c:\xampp\htdocs\caas_nuevo\`

**2. Importar la base de datos**
- Abrir `http://localhost/phpmyadmin`
- Ir a la pestaña **Importar**
- Seleccionar el archivo `caas_v2.sql`
- Clic en **Ejecutar**

**3. Verificar configuración**
En `config.php` confirmar que los datos de conexión sean correctos:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'caas_v2');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**4. Crear el administrador**
Entrar a:
```
http://localhost/caas_nuevo/setup_admin.php
```
Completar el formulario con la clave de seguridad del equipo.
El archivo se elimina automáticamente después del registro.

**5. Acceder al sistema**
```
http://localhost/caas_nuevo/
```

---

## Seguridad Implementada

| Medida | Descripción |
|--------|-------------|
| Hashing de contraseñas | bcrypt con cost 12 via `password_hash()` |
| Prepared statements | Todas las queries usan PDO con parámetros |
| Sanitización XSS | Función `e()` con `htmlspecialchars()` en toda salida HTML |
| Sesiones seguras | `httponly`, `samesite: Lax`, regeneración de ID en login |
| Control de acceso | Verificación de rol en cada página y endpoint |
| Validación de archivos | Verificación de MIME type real en subida de imágenes |
| Endpoints protegidos | Los AJAX verifican sesión y rol antes de responder |

---

## Restricciones del Proyecto

- Sin chat en vivo (límites de hosting gratuito)
- Sin app nativa — diseño 100% responsive para móviles
- Sin pasarela de pago integrada — el pago se coordina por WhatsApp

---

## Equipo

**C.A.A.S. — Comida Al Alcance Siempre**
Proyecto Final — 3° Bachillerato Tecnológico en Informática

📧 comidaallacancesiempre@gmail.com
🔗 https://github.com/comidaalalcancesiempre-oss/caas
