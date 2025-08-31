USE yenipazaryeri;

-- Ürün onay akışı
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS review_status ENUM('pending','approved','rejected') DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS reviewed_by INT NULL,
  ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS review_note VARCHAR(255) NULL;

-- Bildirim kanalları
CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  user_id INT NULL,
  channel ENUM('inapp','email','webhook') NOT NULL DEFAULT 'inapp',
  title VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  url VARCHAR(255) NULL,
  delivered_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tenant_time (tenant_id,created_at)
);

-- Webhook endpoints
CREATE TABLE IF NOT EXISTS tenant_webhooks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  event VARCHAR(64) NOT NULL,
  target_url VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_event_url (tenant_id,event,target_url)
);

-- Batch jobs için batch_id kolonu ekle
ALTER TABLE jobs ADD COLUMN IF NOT EXISTS batch_id VARCHAR(64) NULL;
