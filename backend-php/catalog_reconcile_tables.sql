USE yenipazaryeri;

-- Marketplace kategorileri (cache)
CREATE TABLE IF NOT EXISTS marketplace_categories (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  marketplace_id INT NOT NULL,         -- 1 TY, 2 WOO
  external_id VARCHAR(128) NOT NULL,
  parent_external_id VARCHAR(128) NULL,
  name VARCHAR(190) NOT NULL,
  path VARCHAR(512) NULL,               -- "Kadın>Takı>Bileklik"
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_mp_cat (marketplace_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bizim internal category (opsiyonel) yoksa products.category_path zaten var, mapping için tablo:
CREATE TABLE IF NOT EXISTS category_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  local_path VARCHAR(512) NOT NULL,      -- "Kadın>Takı>Bileklik"
  marketplace_id INT NOT NULL,           -- 1 TY, 2 WOO
  external_id VARCHAR(128) NOT NULL,     -- Pazaryeri kategori ID
  UNIQUE KEY uniq_map (tenant_id, marketplace_id, local_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attribute eşleme: "color" -> Trendyol'da "Renk", Woo'da "attribute_pa_color" vs
CREATE TABLE IF NOT EXISTS attribute_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  local_key VARCHAR(128) NOT NULL,       -- "color"
  marketplace_id INT NOT NULL,
  external_key VARCHAR(128) NOT NULL,    -- TY: "Renk", WOO: "attribute_pa_color" ya da "Color"
  value_map JSON NULL,                   -- {"Kırmızı":"Red","Mavi":"Blue"} gibi opsiyonel dönüşüm
  UNIQUE KEY uniq_attr (tenant_id, marketplace_id, local_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reconcile raporları: son çekilen stok/fiyat karşılaştırması
CREATE TABLE IF NOT EXISTS reconcile_snapshots (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id INT NULL,
  source VARCHAR(16) NOT NULL,           -- "woo" | "trendyol"
  price DECIMAL(12,2) NULL,
  stock INT NULL,
  taken_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tenant_time (tenant_id, taken_at),
  INDEX idx_prod_var (product_id, variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
