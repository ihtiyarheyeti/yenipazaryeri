-- Mevcut foreign key'leri kaldır
-- Bu script'i MySQL'de çalıştır

-- Foreign key'leri kaldır
ALTER TABLE role_permissions DROP FOREIGN KEY IF EXISTS role_permissions_ibfk_1;
ALTER TABLE role_permissions DROP FOREIGN KEY IF EXISTS role_permissions_ibfk_2;
ALTER TABLE user_roles DROP FOREIGN KEY IF EXISTS user_roles_ibfk_1;
ALTER TABLE user_roles DROP FOREIGN KEY IF EXISTS user_roles_ibfk_2;

-- Tabloları temizle
TRUNCATE TABLE role_permissions;
TRUNCATE TABLE user_roles;
TRUNCATE TABLE permissions;
TRUNCATE TABLE roles;
TRUNCATE TABLE users;

-- Şimdi verileri ekle
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
