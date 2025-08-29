-- Tüm foreign key'leri temizle
-- Bu script'i MySQL'de çalıştır

-- Önce tüm foreign key'leri kaldır
SET FOREIGN_KEY_CHECKS = 0;

-- Tabloları tamamen sil ve yeniden oluştur
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS notifications;

-- Foreign key kontrolünü tekrar aç
SET FOREIGN_KEY_CHECKS = 1;

-- Şimdi temiz tabloları oluştur
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL
);

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (user_id, role_id)
);

-- Superadmin verilerini ekle
INSERT INTO roles (id, name) VALUES (1, 'superadmin');

INSERT INTO permissions (id, name) VALUES 
(1, 'system.superadmin'),
(2, 'users.manage'),
(3, 'products.read'),
(4, 'products.write');

INSERT INTO role_permissions (role_id, permission_id) VALUES 
(1, 1), (1, 2), (1, 3), (1, 4);

INSERT INTO users (email, password_hash, name) VALUES 
('superadmin@yenipazaryeri.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin');

INSERT INTO user_roles (user_id, role_id) VALUES 
((SELECT id FROM users WHERE email = 'superadmin@yenipazaryeri.com'), 1);

-- Sonuçları göster
SELECT 
  u.id,
  u.email,
  u.name,
  r.name as role
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE u.email = 'superadmin@yenipazaryeri.com';
