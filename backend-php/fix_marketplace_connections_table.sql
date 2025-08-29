USE entegrasyon_paneli;

-- Mevcut marketplace_connections tablosunu kontrol et
SHOW TABLES LIKE 'marketplace_connections';

-- Eğer tablo varsa yapısını kontrol et
DESCRIBE marketplace_connections;

-- Eğer tablo yoksa oluştur
CREATE TABLE IF NOT EXISTS marketplace_connections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  marketplace_id INT NOT NULL,
  api_key VARCHAR(255) NOT NULL,
  api_secret VARCHAR(255) NOT NULL,
  base_url VARCHAR(500) NULL,
  supplier_id VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tenant_mp (tenant_id, marketplace_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eğer tablo varsa ama marketplace_id kolonu yoksa ekle
ALTER TABLE marketplace_connections 
  ADD COLUMN IF NOT EXISTS marketplace_id INT NOT NULL AFTER tenant_id,
  ADD COLUMN IF NOT EXISTS api_key VARCHAR(255) NOT NULL AFTER marketplace_id,
  ADD COLUMN IF NOT EXISTS api_secret VARCHAR(255) NOT NULL AFTER api_key,
  ADD COLUMN IF NOT EXISTS base_url VARCHAR(500) NULL AFTER api_secret,
  ADD COLUMN IF NOT EXISTS supplier_id VARCHAR(100) NULL AFTER base_url,
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER supplier_id,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Eğer index yoksa ekle
ALTER TABLE marketplace_connections 
  ADD INDEX IF NOT EXISTS idx_tenant_mp (tenant_id, marketplace_id);

-- Kontrol et
SELECT * FROM marketplace_connections;
