-- Users tablosunu tenant_id ile birlikte oluştur
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  tenant_id INT NOT NULL DEFAULT 1,
  role VARCHAR(50) NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Test kullanıcısını ekle
INSERT INTO users (email, password_hash, name, tenant_id, role) VALUES 
('test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 1, 'admin')
ON DUPLICATE KEY UPDATE 
  password_hash = VALUES(password_hash),
  name = VALUES(name),
  role = VALUES(role);
