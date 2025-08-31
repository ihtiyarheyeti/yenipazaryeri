-- Rate Limiting ve Güvenlik Güncellemeleri
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(64) NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_email_ip (email, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index ekle (performance için)
CREATE INDEX idx_login_attempts_email_ip ON login_attempts(email, ip);
CREATE INDEX idx_login_attempts_locked_until ON login_attempts(locked_until);
