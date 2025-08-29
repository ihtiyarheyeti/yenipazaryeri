-- Superadmin kullanıcısı oluştur
-- Bu script'i MySQL'de çalıştır

-- Önce users tablosunu kontrol et (yoksa oluştur)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  email_verified BOOLEAN DEFAULT FALSE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Superadmin kullanıcısını ekle
-- Şifre: superadmin123 (bcrypt hash)
INSERT INTO users (email, password_hash, name, is_active, email_verified) VALUES 
('superadmin@yenipazaryeri.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', TRUE, TRUE)
ON DUPLICATE KEY UPDATE 
  password_hash = VALUES(password_hash),
  name = VALUES(name),
  is_active = TRUE,
  email_verified = TRUE;

-- Superadmin rolünü ekle (yoksa)
INSERT IGNORE INTO roles (id, name) VALUES (0, 'superadmin');

-- Superadmin yetkilerini ekle
INSERT IGNORE INTO permissions (id, name) VALUES 
(100, 'system.superadmin'),
(101, 'users.manage'),
(102, 'roles.manage'),
(103, 'permissions.manage'),
(104, 'system.config'),
(105, 'audit.full_access');

-- Superadmin rolüne tüm yetkileri ver
INSERT IGNORE INTO role_permissions (role_id, permission_id) 
SELECT 0, id FROM permissions;

-- Superadmin kullanıcısına superadmin rolünü ver
INSERT IGNORE INTO user_roles (user_id, role_id) VALUES 
((SELECT id FROM users WHERE email = 'superadmin@yenipazaryeri.com'), 0);

-- Mevcut tüm yetkileri de ekle
INSERT IGNORE INTO role_permissions (role_id, permission_id) 
SELECT 0, id FROM permissions WHERE id < 100;

-- Sonuçları göster
SELECT 
  u.id,
  u.email,
  u.name,
  u.is_active,
  r.name as role,
  GROUP_CONCAT(p.name) as permissions
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE u.email = 'superadmin@yenipazaryeri.com'
GROUP BY u.id, u.email, u.name, u.is_active, r.name;
