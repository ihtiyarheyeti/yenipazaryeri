-- Final Superadmin kullanıcısı oluştur
-- Bu script'i MySQL'de çalıştır

-- 1. Önce tenants tablosunu oluştur
CREATE TABLE IF NOT EXISTS tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  domain VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Default tenant ekle
INSERT IGNORE INTO tenants (id, name, domain) VALUES (1, 'Default Tenant', 'localhost');

-- 3. Users tablosunu oluştur
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  tenant_id INT DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Users tablosuna foreign key ekle
ALTER TABLE users ADD CONSTRAINT fk_users_tenant 
FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

-- 5. Users tablosuna ek kolonlar ekle
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE;

-- 6. Roles tablosunu oluştur
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  tenant_id INT DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 7. Roles tablosuna foreign key ekle
ALTER TABLE roles ADD CONSTRAINT fk_roles_tenant 
FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

-- 8. Permissions tablosunu oluştur
CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 9. Role permissions tablosunu oluştur
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id)
);

-- 10. User roles tablosunu oluştur
CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (user_id, role_id)
);

-- 11. Foreign key'leri ekle (tablolar oluşturulduktan sonra)
ALTER TABLE role_permissions ADD CONSTRAINT fk_role_permissions_role 
FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;

ALTER TABLE role_permissions ADD CONSTRAINT fk_role_permissions_permission 
FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;

ALTER TABLE user_roles ADD CONSTRAINT fk_user_roles_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE user_roles ADD CONSTRAINT fk_user_roles_role 
FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;

-- 12. Superadmin rolünü ekle
INSERT IGNORE INTO roles (id, name, tenant_id) VALUES (1, 'superadmin', 1);

-- 13. Superadmin yetkilerini ekle
INSERT IGNORE INTO permissions (id, name) VALUES 
(1, 'system.superadmin'),
(2, 'users.manage'),
(3, 'roles.manage'),
(4, 'permissions.manage'),
(5, 'system.config'),
(6, 'audit.full_access'),
(7, 'products.read'),
(8, 'products.write'),
(9, 'products.delete'),
(10, 'users.read'),
(11, 'logs.read'),
(12, 'audit.read');

-- 14. Superadmin rolüne tüm yetkileri ver
INSERT IGNORE INTO role_permissions (role_id, permission_id) 
SELECT 1, id FROM permissions;

-- 15. Superadmin kullanıcısını ekle
INSERT INTO users (email, password_hash, name, tenant_id, is_active, email_verified) VALUES 
('superadmin@yenipazaryeri.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 1, TRUE, TRUE)
ON DUPLICATE KEY UPDATE 
  password_hash = VALUES(password_hash),
  name = VALUES(name),
  tenant_id = VALUES(tenant_id),
  is_active = TRUE,
  email_verified = TRUE;

-- 16. Superadmin kullanıcısına superadmin rolünü ver
INSERT IGNORE INTO user_roles (user_id, role_id) VALUES 
((SELECT id FROM users WHERE email = 'superadmin@yenipazaryeri.com'), 1);

-- 17. Sonuçları göster
SELECT 
  u.id,
  u.email,
  u.name,
  u.is_active,
  t.name as tenant,
  r.name as role,
  GROUP_CONCAT(p.name) as permissions
FROM users u
JOIN tenants t ON u.tenant_id = t.id
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE u.email = 'superadmin@yenipazaryeri.com'
GROUP BY u.id, u.email, u.name, u.is_active, t.name, r.name;
