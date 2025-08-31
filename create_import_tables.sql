USE entegrasyon_paneli;

-- Import cursor tablosu
CREATE TABLE IF NOT EXISTS import_cursors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  marketplace_id INT NOT NULL,                 -- 1 TY, 2 WOO
  cursor_key VARCHAR(64) NOT NULL,             -- 'products'
  page INT DEFAULT 0,
  updated_since DATETIME NULL,                 -- artımsal çekim için
  last_run DATETIME NULL,
  UNIQUE KEY uniq_cursor (tenant_id, marketplace_id, cursor_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products tablosuna yeni kolonlar ekle
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS brand VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS description TEXT NULL,
  ADD COLUMN IF NOT EXISTS category_path VARCHAR(512) NULL;

-- Variants tablosuna attrs_json kolonu ekle
ALTER TABLE variants
  ADD COLUMN IF NOT EXISTS attrs_json JSON NULL;

-- Mock ürünler ekle (test için)
INSERT IGNORE INTO products (tenant_id, name, brand, description, category_path, status, created_at, updated_at) VALUES
(1, 'Test Ürün 1', 'Test Marka', 'Test ürün açıklaması', 'Elektronik/Telefon', 'active', NOW(), NOW()),
(1, 'Test Ürün 2', 'Test Marka', 'Test ürün açıklaması 2', 'Giyim/Pantolon', 'active', NOW(), NOW());

-- Mock varyantlar ekle
INSERT IGNORE INTO variants (product_id, sku, price, stock, attrs_json, created_at, updated_at) VALUES
(1, 'TEST001', 99.99, 50, '{"renk": "Siyah", "boyut": "M"}', NOW(), NOW()),
(1, 'TEST002', 89.99, 30, '{"renk": "Beyaz", "boyut": "L"}', NOW(), NOW()),
(2, 'TEST003', 149.99, 25, '{"renk": "Mavi", "boyut": "32"}', NOW(), NOW());
