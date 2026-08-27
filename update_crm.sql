
CREATE TABLE IF NOT EXISTS `crm_pipelines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `crm_stages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pipeline_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(20) DEFAULT '#ffffff',
    `order_index` INT DEFAULT 0,
    `is_won` TINYINT(1) DEFAULT 0,
    `is_lost` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`pipeline_id`) REFERENCES `crm_pipelines`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `crm_prospects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pipeline_id` INT NOT NULL,
    `stage_id` INT NOT NULL,
    `nombre_completo` VARCHAR(150) NOT NULL,
    `documento` VARCHAR(20),
    `telefono` VARCHAR(50),
    `correo` VARCHAR(150),
    `direccion` TEXT,
    `latitud` DECIMAL(10, 8),
    `longitud` DECIMAL(11, 8),
    `fuente` VARCHAR(50),
    `score` INT DEFAULT 0,
    `assigned_to` INT,
    `interest_service_id` INT,
    `reserved_sku_id` INT,
    `lost_reason` TEXT,
    `last_activity_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`pipeline_id`) REFERENCES `crm_pipelines`(`id`),
    FOREIGN KEY (`stage_id`) REFERENCES `crm_stages`(`id`),
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`interest_service_id`) REFERENCES `servicios`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reserved_sku_id`) REFERENCES `inventory_skus`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `crm_tags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `color` VARCHAR(20) DEFAULT '#000000'
);

CREATE TABLE IF NOT EXISTS `crm_prospect_tags` (
    `prospect_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    PRIMARY KEY (`prospect_id`, `tag_id`),
    FOREIGN KEY (`prospect_id`) REFERENCES `crm_prospects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`) REFERENCES `crm_tags`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `crm_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prospect_id` INT NOT NULL,
    `user_id` INT,
    `type` ENUM('nota', 'llamada', 'whatsapp', 'sistema') DEFAULT 'nota',
    `content` TEXT NOT NULL,
    `call_duration` INT DEFAULT NULL,
    `call_result` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`prospect_id`) REFERENCES `crm_prospects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `link_url` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Insertar un pipeline por defecto y sus etapas
INSERT INTO `crm_pipelines` (`name`) VALUES ('Ventas Generales');
INSERT INTO `crm_stages` (`pipeline_id`, `name`, `color`, `order_index`, `is_won`, `is_lost`) VALUES 
(1, 'Nuevos', 'var(--dash-orange)', 1, 0, 0),
(1, 'En Contacto', 'var(--dash-blue)', 2, 0, 0),
(1, 'Cotización', 'var(--dash-purple)', 3, 0, 0),
(1, 'Ganado', 'var(--dash-emerald)', 4, 1, 0),
(1, 'Perdido', 'var(--text-muted)', 5, 0, 1);

