USE entegrasyon_paneli;

-- İade/iptal kayıtları (Trendyol referansıyla)
CREATE TABLE IF NOT EXISTS mp_returns (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  mp VARCHAR(16) NOT NULL DEFAULT 'trendyol',
  external_id VARCHAR(128) NOT NULL,      -- Trendyol return/claim id
  order_external_id VARCHAR(128) NULL,
  reason VARCHAR(255) NULL,
  status ENUM('requested','accepted','rejected','completed') DEFAULT 'requested',
  requested_at DATETIME NULL,
  resolved_at DATETIME NULL,
  payload JSON NULL,
  UNIQUE KEY uniq_ret (tenant_id, mp, external_id),
  INDEX idx_tenant_time (tenant_id, requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mp_cancellations (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  mp VARCHAR(16) NOT NULL DEFAULT 'trendyol',
  external_id VARCHAR(128) NOT NULL,      -- cancel id / order id + line
  order_external_id VARCHAR(128) NULL,
  reason VARCHAR(255) NULL,
  status ENUM('requested','approved','denied') DEFAULT 'requested',
  requested_at DATETIME NULL,
  resolved_at DATETIME NULL,
  payload JSON NULL,
  UNIQUE KEY uniq_can (tenant_id, mp, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shipment (kargo) kayıtları ve label url
CREATE TABLE IF NOT EXISTS shipments (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  mp VARCHAR(16) NOT NULL,                 -- woo|trendyol|internal
  order_id BIGINT NULL,                    -- orders.id
  order_external_id VARCHAR(128) NULL,     -- mp order id
  carrier VARCHAR(64) NULL,                -- Yurtiçi, Aras, etc.
  tracking_no VARCHAR(128) NULL,
  label_url VARCHAR(255) NULL,
  status ENUM('created','label_ready','shipped','delivered','failed') DEFAULT 'created',
  payload JSON NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order (order_id),
  INDEX idx_ext (order_external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoice (e-fatura/e-arşiv) kayıtları
CREATE TABLE IF NOT EXISTS invoices (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  mp VARCHAR(16) NOT NULL,
  order_id BIGINT NULL,
  order_external_id VARCHAR(128) NULL,
  number VARCHAR(64) NULL,
  status ENUM('pending','generated','sent','failed') DEFAULT 'pending',
  pdf_url VARCHAR(255) NULL,
  payload JSON NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order_i (order_id),
  INDEX idx_ext_i (order_external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uyuşmazlık politikaları ve öneriler
CREATE TABLE IF NOT EXISTS policies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  key_name VARCHAR(64) NOT NULL,           -- stock_master, price_master, auto_fix_threshold
  value_json JSON NOT NULL,
  UNIQUE KEY uniq_policy (tenant_id, key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reconcile_suggestions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  product_id INT NULL,
  variant_id INT NULL,
  issue ENUM('stock_mismatch','price_mismatch') NOT NULL,
  local_value VARCHAR(64) NULL,
  remote_value VARCHAR(64) NULL,
  source VARCHAR(16) NOT NULL,            -- remote source which differs (woo|trendyol)
  suggestion VARCHAR(255) NOT NULL,       -- "set woo to local", "set local to trendyol" vb.
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  resolved_at DATETIME NULL,
  resolution_note VARCHAR(255) NULL,
  INDEX idx_tenant_issue (tenant_id, issue, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Varsayılan politikalar (yerel master kabul edelim)
INSERT IGNORE INTO policies(tenant_id,key_name,value_json) VALUES
(1,'stock_master', JSON_OBJECT('master','local')),       -- local|woo|trendyol
(1,'price_master', JSON_OBJECT('master','local')),
(1,'auto_fix_threshold', JSON_OBJECT('price', 0.02, 'stock', 0));  -- %2 fiyat farkı üzerinde öneri
