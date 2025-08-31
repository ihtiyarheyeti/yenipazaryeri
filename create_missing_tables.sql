USE entegrasyon_paneli;

-- Variants tablosu (products tablosundaki variants JSON kolonunu ayrı tabloya taşımak için)
CREATE TABLE IF NOT EXISTS variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  sku VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  attrs_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_product_sku (product_id, sku),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product marketplace mapping tablosu
CREATE TABLE IF NOT EXISTS product_marketplace_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  marketplace_id INT NOT NULL, -- 1: Trendyol, 2: WooCommerce
  external_id VARCHAR(255) NULL,
  external_sku VARCHAR(255) NULL,
  sync_status ENUM('pending', 'synced', 'error', 'deleted') DEFAULT 'pending',
  last_sync DATETIME NULL,
  sync_errors TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_product_marketplace (product_id, marketplace_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders_new tablosu (siparişler için)
CREATE TABLE IF NOT EXISTS orders_new (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  external_id VARCHAR(255) NOT NULL,
  marketplace_name VARCHAR(50) NOT NULL, -- 'trendyol', 'woocommerce'
  customer_name VARCHAR(255) NULL,
  customer_email VARCHAR(255) NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'TRY',
  status VARCHAR(50) NOT NULL,
  order_date DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_marketplace_order (tenant_id, marketplace_name, external_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items tablosu
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_sku VARCHAR(255) NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders_new(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Returns tablosu
CREATE TABLE IF NOT EXISTS returns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  external_id VARCHAR(255) NOT NULL,
  order_external_id VARCHAR(255) NOT NULL,
  marketplace_name VARCHAR(50) NOT NULL,
  reason VARCHAR(255) NULL,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  return_date DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_marketplace_return (tenant_id, marketplace_name, external_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cancellations tablosu
CREATE TABLE IF NOT EXISTS cancellations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  external_id VARCHAR(255) NOT NULL,
  order_external_id VARCHAR(255) NOT NULL,
  marketplace_name VARCHAR(50) NOT NULL,
  reason VARCHAR(255) NULL,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  cancel_date DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_marketplace_cancel (tenant_id, marketplace_name, external_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shipments tablosu
CREATE TABLE IF NOT EXISTS shipments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  order_external_id VARCHAR(255) NOT NULL,
  marketplace_name VARCHAR(50) NOT NULL,
  carrier VARCHAR(100) NOT NULL,
  tracking_no VARCHAR(255) NULL,
  label_url VARCHAR(500) NULL,
  status ENUM('pending', 'label_ready', 'shipped', 'delivered') DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Invoices tablosu
CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  order_external_id VARCHAR(255) NOT NULL,
  marketplace_name VARCHAR(50) NOT NULL,
  invoice_no VARCHAR(255) NULL,
  pdf_url VARCHAR(500) NULL,
  status ENUM('pending', 'generated', 'sent') DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jobs tablosu (background işler için)
CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(100) NOT NULL,
  status ENUM('pending', 'processing', 'completed', 'error', 'dead') DEFAULT 'pending',
  payload JSON NULL,
  attempts INT DEFAULT 0,
  max_attempts INT DEFAULT 5,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status_type (status, type),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import cursors tablosu
CREATE TABLE IF NOT EXISTS import_cursors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  marketplace_id INT NOT NULL,
  cursor_key VARCHAR(64) NOT NULL,
  page INT DEFAULT 0,
  updated_since DATETIME NULL,
  last_run DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_cursor (tenant_id, marketplace_id, cursor_key),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reconcile suggestions tablosu
CREATE TABLE IF NOT EXISTS reconcile_suggestions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  product_id INT NOT NULL,
  variant_id INT NULL,
  issue ENUM('stock_mismatch', 'price_mismatch', 'category_mismatch', 'attribute_mismatch') NOT NULL,
  source ENUM('trendyol', 'woocommerce') NOT NULL,
  local_value TEXT NULL,
  remote_value TEXT NULL,
  suggestion TEXT NULL,
  status ENUM('pending', 'resolved', 'ignored') DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Policies tablosu
CREATE TABLE IF NOT EXISTS policies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  policy_key VARCHAR(100) NOT NULL,
  policy_value JSON NOT NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_policy (tenant_id, policy_key),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Test verileri ekle
INSERT IGNORE INTO variants (product_id, sku, price, stock, attrs_json) 
SELECT id, seller_sku, price, stock, variants 
FROM products 
WHERE variants IS NOT NULL AND seller_sku IS NOT NULL;

-- Test ürün mapping ekle
INSERT IGNORE INTO product_marketplace_mapping (product_id, marketplace_id, external_sku, sync_status)
SELECT id, 1, seller_sku, 'pending' FROM products WHERE seller_sku IS NOT NULL;

-- Test tenant ekle (eğer yoksa)
INSERT IGNORE INTO tenants (id, name, slug, company_name, email) VALUES 
(1, 'Test Şirket', 'test', 'Test Şirket A.Ş.', 'test@example.com');

-- Test user ekle (eğer yoksa)
INSERT IGNORE INTO users (id, name, email, password, role) VALUES 
(1, 'Admin User', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
