-- Jobs tablosu oluştur
CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(50) NOT NULL, -- 'trendyol_send_product' | 'woo_send_product'
  payload JSON NOT NULL,     -- {"product_id":10}
  status ENUM('pending','running','done','error') DEFAULT 'pending',
  attempts INT DEFAULT 0,
  last_error TEXT NULL,
  scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Marketplaces seed (yoksa)
INSERT IGNORE INTO marketplaces (id,name,base_url) VALUES
(1,'Trendyol','https://api.trendyol.com'),
(2,'WooCommerce','http://localhost/wp-json/wc/v3');
