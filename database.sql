-- Base de datos para Agencia de Viajes Ecuador
-- Hostinger Database: u240362798_ToursEc

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `role` enum('admin','staff') DEFAULT 'staff',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de destinos
CREATE TABLE IF NOT EXISTS `destinations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) DEFAULT NULL,
    `name` varchar(200) NOT NULL,
    `slug` varchar(200) NOT NULL,
    `province` varchar(100) DEFAULT NULL,
    `short_desc` text,
    `description` text,
    `image_url` text,
    `gallery` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT '1',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `destinations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tours
CREATE TABLE IF NOT EXISTS `tours` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `destination_id` int(11) DEFAULT NULL,
    `title` varchar(200) NOT NULL,
    `slug` varchar(200) NOT NULL,
    `duration_days` int(11) NOT NULL,
    `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
    `base_price` decimal(10,2) NOT NULL,
    `currency` char(3) DEFAULT 'USD',
    `highlights` json DEFAULT NULL,
    `image_url` text,
    `is_active` tinyint(1) DEFAULT '1',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `destination_id` (`destination_id`),
    CONSTRAINT `tours_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de horarios/fechas
CREATE TABLE IF NOT EXISTS `schedules` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tour_id` int(11) NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `seats_total` int(11) NOT NULL DEFAULT '20',
    `seats_taken` int(11) NOT NULL DEFAULT '0',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_schedule` (`tour_id`,`start_date`,`end_date`),
    CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reservas
CREATE TABLE IF NOT EXISTS `reservations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `schedule_id` int(11) NOT NULL,
    `customer_name` varchar(100) NOT NULL,
    `customer_email` varchar(100) NOT NULL,
    `customer_phone` varchar(20) DEFAULT NULL,
    `pax` int(11) NOT NULL DEFAULT '1',
    `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
    `notes` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `schedule_id` (`schedule_id`),
    CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de ejemplo

-- Usuario administrador
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
('Administrador', 'admin@campingec.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categorías
INSERT INTO `categories` (`name`, `slug`) VALUES
('Tours', 'tours'),
('Campings', 'campings'),
('Destinos', 'destinos');

-- Destinos de ejemplo
INSERT INTO `destinations` (`category_id`, `name`, `slug`, `province`, `short_desc`, `description`, `image_url`, `gallery`, `is_active`) VALUES
(1, 'Baños de Agua Santa', 'banos-agua-santa', 'Tungurahua', 'La puerta de entrada a la Amazonía ecuatoriana', 'Baños es conocido como la "Puerta de entrada a la Amazonía" y es famoso por sus aguas termales, deportes extremos y hermosos paisajes. Ubicado al pie del volcán Tungurahua, ofrece una experiencia única entre montañas y selva.', 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=800&h=600&fit=crop', '["https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800", "https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800", "https://images.unsplash.com/photo-1426604966848-d7adac402bff?w=800"]', 1),

(1, 'Quilotoa', 'quilotoa', 'Cotopaxi', 'Laguna volcánica en los Andes ecuatorianos', 'El Quilotoa es una caldera volcánica llena de agua que forma una laguna de color verde esmeralda. Es uno de los destinos más fotografiados del Ecuador y forma parte del famoso "Loop del Quilotoa".', 'https://images.unsplash.com/photo-1555400297-4c50c0b7eb6b?w=800&h=600&fit=crop', '["https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=800", "https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=800"]', 1),

(2, 'Mindo Cloud Forest', 'mindo-cloud-forest', 'Pichincha', 'Bosque nublado lleno de biodiversidad', 'Mindo es un paraíso para los amantes de la naturaleza y observadores de aves. Su bosque nublado alberga más de 500 especies de aves y ofrece aventuras como canopy, tubing y caminatas por senderos naturales.', 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800&h=600&fit=crop', '["https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=800", "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800"]', 1);

-- Tours de ejemplo
INSERT INTO `tours` (`destination_id`, `title`, `slug`, `duration_days`, `difficulty`, `base_price`, `currency`, `highlights`, `image_url`, `is_active`) VALUES
(1, 'Aventura Extrema en Baños 3 Días', 'aventura-extrema-banos-3-dias', 3, 'medium', 299.00, 'USD', '["Puenting desde el puente de San Francisco", "Casa del Árbol y Columpio del Fin del Mundo", "Ruta de las Cascadas en bicicleta", "Aguas termales de la Virgen", "Rafting en el río Pastaza"]', 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=800&h=600&fit=crop', 1),

(2, 'Quilotoa Loop Trekking 2 Días', 'quilotoa-loop-trekking-2-dias', 2, 'hard', 199.00, 'USD', '["Caminata alrededor de la laguna del Quilotoa", "Visita a comunidades indígenas", "Paisajes andinos espectaculares", "Alojamiento en hostería local", "Guía especializado en trekking"]', 'https://images.unsplash.com/photo-1555400297-4c50c0b7eb6b?w=800&h=600&fit=crop', 1),

(3, 'Observación de Aves en Mindo 2 Días', 'observacion-aves-mindo-2-dias', 2, 'easy', 159.00, 'USD', '["Avistamiento de más de 300 especies", "Visita a mariposario", "Caminata nocturna en el bosque", "Canopy en el dosel del bosque", "Degustación de chocolate artesanal"]', 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800&h=600&fit=crop', 1);

-- Horarios de ejemplo
INSERT INTO `schedules` (`tour_id`, `start_date`, `end_date`, `seats_total`, `seats_taken`) VALUES
(1, '2024-02-15', '2024-02-17', 12, 3),
(1, '2024-02-22', '2024-02-24', 12, 0),
(1, '2024-03-01', '2024-03-03', 12, 5),
(1, '2024-03-08', '2024-03-10', 12, 0),

(2, '2024-02-20', '2024-02-21', 8, 2),
(2, '2024-02-27', '2024-02-28', 8, 0),
(2, '2024-03-05', '2024-03-06', 8, 1),

(3, '2024-02-18', '2024-02-19', 10, 4),
(3, '2024-02-25', '2024-02-26', 10, 0),
(3, '2024-03-03', '2024-03-04', 10, 2);

-- Reservas de ejemplo
INSERT INTO `reservations` (`schedule_id`, `customer_name`, `customer_email`, `customer_phone`, `pax`, `status`, `notes`) VALUES
(1, 'María García', 'maria@email.com', '0987654321', 2, 'confirmed', 'Vegetariana'),
(1, 'Carlos López', 'carlos@email.com', '0998765432', 1, 'pending', ''),
(5, 'Ana Rodríguez', 'ana@email.com', '0976543210', 2, 'confirmed', 'Primera vez en trekking'),
(8, 'Jorge Mendoza', 'jorge@email.com', '0965432109', 4, 'pending', 'Grupo familiar con niños');

COMMIT;
