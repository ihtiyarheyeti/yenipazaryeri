-- Basit Superadmin kullanıcısı oluştur
-- Bu script'i MySQL'de çalıştır

-- 1. Tenants tablosu (eğer yoksa)
CREATE TABLE IF NOT EXISTS tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL
);

-- 2. Default tenant ekle
INSERT INTO tenants (id, name) VALUES (1, 'Default Tenant') ON DUPLICATE KEY UPDATE name = name;

-- 3. Users tablosu (eğer yoksa)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  tenant_id INT DEFAULT 1
);

-- 4. Roles tablosu (eğer yoksa)
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  tenant_id INT DEFAULT 1
);

-- 5. Permissions tablosu (eğer yoksa)
CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

-- 6. Role permissions tablosu (eğer yoksa)
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id)
);

-- 7. User roles tablosu (eğer yoksa)
CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (user_id, role_id)
);

-- 8. Superadmin rolünü ekle
INSERT INTO roles (id, name, tenant_id) VALUES (1, 'superadmin', 1) ON DUPLICATE KEY UPDATE name = name;

-- 9. Temel yetkileri ekle
INSERT INTO permissions (id, name) VALUES 
(1, 'system.superadmin'),
(2, 'users.manage'),
(3, 'products.read'),
(4, 'products.write')
ON DUPLICATE KEY UPDATE name = name;

-- 10. Superadmin rolüne yetkileri ver
INSERT INTO role_permissions (role_id, permission_id) VALUES 
(1, 1), (1, 2), (1, 3), (1, 4)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- 11. Superadmin kullanıcısını ekle
INSERT INTO users (email, password_hash, name, tenant_id) VALUES 
('superadmin@yenipazaryeri.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 1)
ON DUPLICATE KEY UPDATE 
  password_hash = VALUES(password_hash),
  name = VALUES(name);

-- 12. Superadmin kullanıcısına rolü ver
INSERT INTO user_roles (user_id, role_id) VALUES 
((SELECT id FROM users WHERE email = 'superadmin@yenipazaryeri.com'), 1)
ON DUPLICATE KEY UPDATE user_id = user_id;

-- 13. Sonuçları göster
SELECT 
  u.id,
  u.email,
  u.name,
  r.name as role
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
WHERE u.email = 'superadmin@yenipazaryeri.com';
