CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    pin VARCHAR(10) UNIQUE DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contraseña por defecto: admin123 (y PIN de ejemplo 1234)
INSERT INTO users (name, email, pin, password, role) VALUES 
('Admin', 'admin@turbosaas.com', '1234', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE id=id;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
);

-- Insertar configuraciones base
INSERT INTO settings (setting_key, setting_value) VALUES 
('app_name', 'Turbo SaaS'),
('slogan', 'Tu plataforma de gestión en la nube'),
('ruc', ''),
('contact_email', 'contacto@turbosaas.com'),
('website', ''),
('phone_main', ''),
('phone_secondary', ''),
('social_links', '{}'),
('currency', 'USD'),
('work_hours', ''),
('date_format', 'Y-m-d'),
('bg_color', '#f8f9fa'),
('text_color', '#333333'),
('hover_effect', 'scale'),
('typography', 'Inter'),
('logo_light', ''),
('logo_dark', ''),
('logo_pwa', ''),
('favicon', ''),
('global_notification_banner', ''),
('global_notification_push', '0'),
('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE setting_value=setting_value;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    module VARCHAR(100) NOT NULL,
    can_view BOOLEAN DEFAULT TRUE,
    can_create BOOLEAN DEFAULT FALSE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS actas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(20) NOT NULL UNIQUE,
    prefijo VARCHAR(10) DEFAULT 'LIM-',
    token VARCHAR(64),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- 1. DATOS DEL CLIENTE
    cliente_nombre VARCHAR(255),
    cliente_dni_ruc VARCHAR(50),
    cliente_direccion TEXT,
    cliente_distrito VARCHAR(100),
    cliente_referencia TEXT,
    cliente_whatsapp VARCHAR(50),
    cliente_celular_alt VARCHAR(50),
    cliente_gps_lat VARCHAR(50),
    cliente_gps_lng VARCHAR(50),
    foto_rostro_path VARCHAR(255),
    
    -- 2. PLANTA EXTERNA
    pe_nodo VARCHAR(100),
    pe_nap VARCHAR(100),
    pe_puerto VARCHAR(50),
    pe_potencia VARCHAR(50),
    pe_atenuacion VARCHAR(50),
    
    -- 3. DETALLES DEL SERVICIO
    srv_fecha DATE,
    srv_hora_inicio TIME,
    srv_hora_fin TIME,
    srv_tipo VARCHAR(100),
    srv_estado VARCHAR(100),
    tecnico_id INT,
    
    -- Configuración Red/TV
    red_ssid VARCHAR(100),
    red_password VARCHAR(100),
    red_speed_dl VARCHAR(50),
    red_speed_ul VARCHAR(50),
    red_n_tvs INT,
    red_splitters VARCHAR(100),
    red_senal_low VARCHAR(50),
    red_senal_high VARCHAR(50),
    
    -- 6. OBSERVACIONES
    observaciones TEXT,
    
    -- 7. CONFORMIDAD Y FIRMAS
    mantenimiento_6_meses BOOLEAN DEFAULT FALSE,
    calificacion_servicio INT DEFAULT 0,
    firma_cliente TEXT,
    firma_tecnico TEXT
);

CREATE TABLE IF NOT EXISTS actas_equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    acta_id INT NOT NULL,
    accion VARCHAR(50),
    modelo_marca VARCHAR(150),
    serie_mac VARCHAR(150),
    propiedad VARCHAR(100),
    FOREIGN KEY (acta_id) REFERENCES actas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS actas_materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    acta_id INT NOT NULL,
    descripcion VARCHAR(255),
    cantidad DECIMAL(10,2),
    unidad VARCHAR(50),
    FOREIGN KEY (acta_id) REFERENCES actas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS actas_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    acta_id INT NOT NULL,
    tipo VARCHAR(50),
    ruta_archivo VARCHAR(255),
    FOREIGN KEY (acta_id) REFERENCES actas(id) ON DELETE CASCADE
);

-- ========================================
-- MÓDULO DE INVENTARIO
-- ========================================

CREATE TABLE IF NOT EXISTS inventory_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    total_quantity INT DEFAULT 0,
    stock_minimo INT DEFAULT 10,
    stock_critico INT DEFAULT 3,
    is_bulk TINYINT(1) DEFAULT 0,
    unit_type VARCHAR(50) DEFAULT 'Unidades',
    custom_columns LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS inventory_skus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku_code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('disponible','instalado','malogrado','reparado','en_transito') DEFAULT 'disponible',
    historia ENUM('ninguno','devuelto','malogrado','antiguo','en_transito') DEFAULT 'ninguno',
    assigned_to INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES inventory_products(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS inventory_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku_id INT NOT NULL,
    user_id INT NOT NULL,
    tipo ENUM('entrada','salida','devolucion','reparacion') DEFAULT 'entrada',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sku_id) REFERENCES inventory_skus(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inventory_entry_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_id) REFERENCES inventory_entries(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inventory_sku_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku_id INT NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    nota VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sku_id) REFERENCES inventory_skus(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- MÓDULO DE CLIENTES
-- ========================================
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    dni VARCHAR(20) NOT NULL,
    celular VARCHAR(20),
    correo VARCHAR(100),
    direccion TEXT,
    referencia TEXT,
    detalles_plan TEXT,
    fecha_servicio_contratado DATETIME,
    inicio_servicio DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- MÓDULO DE SOPORTE
-- ========================================

CREATE TABLE IF NOT EXISTS ticket_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT '#3b82f6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ticket_priorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT '#eab308',
    level INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    asunto VARCHAR(255) NOT NULL,
    categoria_id INT,
    prioridad_id INT,
    assigned_to INT,
    estado ENUM('nuevo', 'pendiente', 'en_proceso', 'terminado', 'eliminado') DEFAULT 'nuevo',
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (prioridad_id) REFERENCES ticket_priorities(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT, -- NULL si es mensaje del sistema (opcional)
    message TEXT,
    is_system_message BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ticket_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES ticket_messages(id) ON DELETE CASCADE
);
