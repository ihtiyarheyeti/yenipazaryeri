-- 2FA ve Güvenlik Güncellemeleri
ALTER TABLE users
  ADD COLUMN twofa_secret VARCHAR(64) NULL,
  ADD COLUMN twofa_enabled TINYINT(1) NOT NULL DEFAULT 0;

-- Şifre sıfırlama tablosu
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mevcut users tablosunda password_hash sütunu yoksa ekle
ALTER TABLE users 
  ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL AFTER password;

-- Eğer password sütunu varsa password_hash'e taşı
UPDATE users SET password_hash = password WHERE password_hash IS NULL AND password IS NOT NULL;

