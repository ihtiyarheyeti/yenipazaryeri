-- Ek tablolar oluştur
USE yenipazaryeri;

-- Marketplace connections tablosu
CREATE TABLE IF NOT EXISTS marketplace_connections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  marketplace_id INT NOT NULL,
  api_key VARCHAR(500),
  api_secret VARCHAR(500),
  supplier_id VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id) ON DELETE CASCADE
);

-- Category mappings tablosu
CREATE TABLE IF NOT EXISTS category_mappings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  marketplace_id INT NOT NULL,
  source_path VARCHAR(500) NOT NULL,
  external_category_id VARCHAR(255) NOT NULL,
  note TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id) ON DELETE CASCADE,
  UNIQUE KEY unique_mapping (tenant_id, marketplace_id, source_path)
);

-- Örnek veriler
INSERT IGNORE INTO marketplace_connections (tenant_id, marketplace_id, api_key, api_secret, supplier_id) VALUES
(1, 1, 'demo_trendyol_key', 'demo_trendyol_secret', '12345'),
(1, 2, 'demo_woo_key', 'demo_woo_secret', NULL);

INSERT IGNORE INTO category_mappings (tenant_id, marketplace_id, source_path, external_category_id, note) VALUES
(1, 1, 'Kadın>Takı>Bileklik', 'TRENDYOL_123', 'Trendyol bileklik kategorisi'),
(1, 1, 'Kadın>Takı>Kolye', 'TRENDYOL_456', 'Trendyol kolye kategorisi'),
(1, 2, 'Kadın>Takı>Bileklik', 'WOO_789', 'WooCommerce bileklik kategorisi');
