CREATE TABLE IF NOT EXISTS `servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `velocidad` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `clientes`
ADD COLUMN IF NOT EXISTS `servicio_id` int(11) NULL,
ADD COLUMN IF NOT EXISTS `latitud` varchar(50) NULL,
ADD COLUMN IF NOT EXISTS `longitud` varchar(50) NULL;

ALTER TABLE `actas`
ADD COLUMN IF NOT EXISTS `servicio_id` int(11) NULL;
