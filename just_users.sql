-- Sadece users tablosu oluştur
-- Bu script'i MySQL'de çalıştır

-- Sadece users tablosu oluştur
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL
);

-- Superadmin kullanıcısını ekle
INSERT INTO users (email, password_hash, name) VALUES 
('superadmin@yenipazaryeri.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin')
ON DUPLICATE KEY UPDATE 
  password_hash = VALUES(password_hash),
  name = VALUES(name);

-- Sonuçları göster
SELECT id, email, name FROM users WHERE email = 'superadmin@yenipazaryeri.com';
