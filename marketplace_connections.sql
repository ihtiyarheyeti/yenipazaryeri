USE yenipazaryeri;

-- Marketplace connections tablosu
CREATE TABLE IF NOT EXISTS marketplace_connections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  marketplace_id INT NOT NULL, -- 1: Trendyol, 2: WooCommerce
  name VARCHAR(190) NOT NULL, -- "Ana Mağaza", "Test Mağaza" gibi
  base_url VARCHAR(512) NOT NULL, -- API base URL
  api_key VARCHAR(255) NOT NULL, -- API anahtarı
  api_secret VARCHAR(255) NOT NULL, -- API gizli anahtarı
  supplier_id VARCHAR(128) NULL, -- Trendyol için supplier ID
  is_active BOOLEAN DEFAULT TRUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_tenant_mp (tenant_id, marketplace_id),
  INDEX idx_marketplace (marketplace_id),
  INDEX idx_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Marketplaces tablosu (eğer yoksa)
CREATE TABLE IF NOT EXISTS marketplaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL, -- "Trendyol", "WooCommerce"
  slug VARCHAR(50) NOT NULL UNIQUE, -- "trendyol", "woocommerce"
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Temel marketplace'leri ekle
INSERT IGNORE INTO marketplaces (id, name, slug) VALUES 
(1, 'Trendyol', 'trendyol'),
(2, 'WooCommerce', 'woocommerce');

-- Örnek connection ekle (test için)
INSERT INTO marketplace_connections (tenant_id, marketplace_id, name, base_url, api_key, api_secret, supplier_id) VALUES
(1, 1, 'Trendyol Test', 'https://api.trendyol.com/sapigw', 'test_key', 'test_secret', '12345'),
(1, 2, 'WooCommerce Test', 'https://shop.example.com/wp-json/wc/v3', 'ck_test', 'cs_test', NULL);
