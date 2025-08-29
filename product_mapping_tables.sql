USE yenipazaryeri;

-- Ürün marketplace mapping tablosu
CREATE TABLE IF NOT EXISTS product_marketplace_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  product_id INT NOT NULL,
  marketplace_id INT NOT NULL, -- 1: Trendyol, 2: WooCommerce
  external_id VARCHAR(255) NOT NULL, -- Pazaryerindeki ürün ID'si
  external_url VARCHAR(512) NULL, -- Pazaryerindeki ürün URL'i
  sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
  last_sync_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_prod_mp (product_id, marketplace_id),
  INDEX idx_marketplace (marketplace_id),
  INDEX idx_tenant (tenant_id),
  INDEX idx_external (external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Varyant marketplace mapping tablosu
CREATE TABLE IF NOT EXISTS variant_marketplace_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  variant_id INT NOT NULL,
  marketplace_id INT NOT NULL, -- 1: Trendyol, 2: WooCommerce
  external_variant_id VARCHAR(255) NOT NULL, -- Pazaryerindeki varyant ID'si
  external_sku VARCHAR(255) NULL, -- Pazaryerindeki SKU
  sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
  last_sync_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY uniq_var_mp (variant_id, marketplace_id),
  INDEX idx_marketplace (marketplace_id),
  INDEX idx_tenant (tenant_id),
  INDEX idx_external (external_variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
