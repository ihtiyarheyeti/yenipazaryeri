-- Entegrasyon tabloları
USE yenipazaryeri;

-- Ürün-pazaryeri eşleştirme tablosu
CREATE TABLE IF NOT EXISTS product_marketplace_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  marketplace_id INT NOT NULL,
  external_id VARCHAR(128) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_prod_mp (product_id, marketplace_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log tablosu
CREATE TABLE IF NOT EXISTS logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NULL,
  product_id INT NULL,
  type VARCHAR(50) NOT NULL,   -- e.g. trendyol_push, woo_push
  status VARCHAR(20) NOT NULL, -- success | error | queued
  message VARCHAR(512) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_tenant (tenant_id),
  INDEX idx_logs_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Job kuyruğu tablosu
CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(50) NOT NULL,
  payload JSON NOT NULL,
  status ENUM('pending','running','done','error') DEFAULT 'pending',
  attempts INT DEFAULT 0,
  next_attempt_at DATETIME NULL,
  last_error TEXT NULL,
  scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
