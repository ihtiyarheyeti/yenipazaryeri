USE entegrasyon_paneli;

-- Orders tablosu
CREATE TABLE IF NOT EXISTS orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  origin_mp ENUM('woo','trendyol') NOT NULL,
  origin_external_id VARCHAR(128) NOT NULL,
  customer_name VARCHAR(255) NULL,
  customer_email VARCHAR(255) NULL,
  total_amount DECIMAL(12,2) DEFAULT 0,
  currency CHAR(3) DEFAULT 'TRY',
  status VARCHAR(40) DEFAULT 'pending',  -- pending|processing|shipped|completed|cancelled|refunded
  shipping_address JSON NULL,
  billing_address JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_tenant_origin (tenant_id, origin_mp, origin_external_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items tablosu
CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  product_id INT NULL,
  variant_id INT NULL,
  sku VARCHAR(128) NULL,
  name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  attrs_json JSON NULL,
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order marketplace mapping tablosu
CREATE TABLE IF NOT EXISTS order_marketplace_mapping (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  marketplace_id INT NOT NULL,  -- 1 TY, 2 WOO
  external_id VARCHAR(128) NOT NULL,
  UNIQUE KEY uniq_order_mp (order_id, marketplace_id),
  INDEX idx_mp_ext (marketplace_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order status history tablosu
CREATE TABLE IF NOT EXISTS order_status_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  status VARCHAR(40) NOT NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT NOW(),
  INDEX (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Orders tables created successfully' as result;
