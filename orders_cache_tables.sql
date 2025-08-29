USE entegrasyon_paneli;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  mp VARCHAR(16) NOT NULL,               -- 'trendyol' | 'woo'
  external_id VARCHAR(128) NOT NULL,     -- mp order id
  status VARCHAR(64) NULL,
  currency VARCHAR(8) NULL,
  total DECIMAL(12,2) NULL,
  customer_name VARCHAR(190) NULL,
  customer_email VARCHAR(190) NULL,
  phone VARCHAR(64) NULL,
  shipping_address JSON NULL,
  billing_address JSON NULL,
  created_at_mp DATETIME NULL,           -- marketplace order created
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_mp_order (tenant_id, mp, external_id),
  INDEX idx_tenant_time (tenant_id, created_at_mp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  product_id INT NULL,
  variant_id INT NULL,
  sku VARCHAR(128) NULL,
  name VARCHAR(190) NULL,
  qty INT NOT NULL DEFAULT 1,
  price DECIMAL(12,2) NULL,
  tax DECIMAL(12,2) NULL,
  total DECIMAL(12,2) NULL,
  attrs JSON NULL,
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
