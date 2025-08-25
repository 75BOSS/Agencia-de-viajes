-- SQL para resetear contraseña del admin
-- Ejecutar en phpMyAdmin si no se puede entrar con las credenciales actuales
-- Hash corresponde a: admin123

UPDATE users
SET password_hash = '$2y$10$QeM1Zv8Ndn6ztIlb0pC6VOfb5W7zYZd7Eqc9gILGnPM0A0Zx8RtV2', role='admin'
WHERE email='admin@campingec.com';

-- Si no existe el usuario admin, crearlo:
INSERT IGNORE INTO users (name, email, password_hash, role) 
VALUES ('Administrador', 'admin@campingec.com', '$2y$10$QeM1Zv8Ndn6ztIlb0pC6VOfb5W7zYZd7Eqc9gILGnPM0A0Zx8RtV2', 'admin');
