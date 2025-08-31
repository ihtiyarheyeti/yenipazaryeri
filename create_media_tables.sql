USE entegrasyon_paneli;

-- Images tablosu
CREATE TABLE IF NOT EXISTS images (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  url VARCHAR(1024) NOT NULL,
  position INT DEFAULT 0,
  hash VARCHAR(64) NULL,
  stored_path VARCHAR(512) NULL,   -- opsiyonel: indirilmiş dosya
  status ENUM('new','ready','error') DEFAULT 'new',
  created_at DATETIME, updated_at DATETIME,
  INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Image marketplace mapping tablosu
CREATE TABLE IF NOT EXISTS image_marketplace_mapping (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  image_id BIGINT NOT NULL,
  marketplace_id INT NOT NULL,  -- 1 TY, 2 WOO
  external_id VARCHAR(128) NOT NULL,
  UNIQUE KEY uniq_img_mp (image_id, marketplace_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Webhook events tablosu
CREATE TABLE IF NOT EXISTS webhook_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  marketplace_id INT NOT NULL,
  event_type VARCHAR(64) NOT NULL,
  payload_json JSON NOT NULL,
  processed TINYINT(1) DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT NOW(),
  INDEX (marketplace_id, processed),
  INDEX (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products tablosuna medya bayrağı ekle
SET @has_media_status := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='media_status');
SET @sql1 := IF(@has_media_status=0, 'ALTER TABLE products ADD COLUMN media_status ENUM("none","partial","ready") DEFAULT "none"', 'SELECT "media_status already exists" as message');
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SELECT 'Media tables created successfully' as result;
