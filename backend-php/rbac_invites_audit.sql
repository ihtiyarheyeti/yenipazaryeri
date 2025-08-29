USE yenipazaryeri;

-- ROL & İZİN
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  name VARCHAR(64) NOT NULL,
  UNIQUE KEY uniq_role (tenant_id,name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL UNIQUE  -- örn: users.read, users.write, roles.write, products.write...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- DAVET & RESET TOKENLARI
CREATE TABLE IF NOT EXISTS invites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  email VARCHAR(190) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  role_id INT NULL,
  expires_at DATETIME NOT NULL,
  accepted_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_invite (tenant_id,email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- AUDIT LOG
CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  user_id INT NULL,
  action VARCHAR(128) NOT NULL,     -- örn: user.create, role.update
  resource VARCHAR(128) NULL,       -- örn: /users, /roles/5
  payload JSON NULL,                -- istenirse küçük özet
  ip VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tenant_time (tenant_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Başlangıç izinleri
INSERT IGNORE INTO permissions(name) VALUES
('users.read'),('users.write'),('roles.read'),('roles.write'),
('settings.write'),('products.read'),('products.write'),('variants.write');

-- Varsayılan rol (Admin) — tenant 1 için, yoksa ekle
INSERT IGNORE INTO roles(tenant_id,name) VALUES (1,'Admin');

-- Admin rolüne temel izinleri ver
INSERT IGNORE INTO role_permissions(role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON 1=1
WHERE r.tenant_id=1 AND r.name='Admin';

-- Admin kullanıcıyı role bağla (users tablosunda admin@example.com varsa)
INSERT IGNORE INTO user_roles(user_id, role_id)
SELECT u.id, r.id FROM users u, roles r
WHERE u.email='admin@example.com' AND r.tenant_id=1 AND r.name='Admin';
