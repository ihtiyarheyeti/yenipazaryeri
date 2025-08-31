USE entegrasyon_paneli;

-- Varyant marketplace mapping tablosu
CREATE TABLE IF NOT EXISTS variant_marketplace_mapping (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  variant_id INT NOT NULL,
  marketplace_id INT NOT NULL, -- 1 TY, 2 WOO
  external_variant_id VARCHAR(128) NOT NULL,
  UNIQUE KEY uniq_var_mp (variant_id, marketplace_id),
  INDEX idx_mp_ext (marketplace_id, external_variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products tablosuna push durum alanları
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS sync_woo_status ENUM('none','queued','ok','error') DEFAULT 'none',
  ADD COLUMN IF NOT EXISTS sync_trendyol_status ENUM('none','queued','ok','error') DEFAULT 'none',
  ADD COLUMN IF NOT EXISTS sync_woo_msg VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS sync_trendyol_msg VARCHAR(255) NULL;

-- (typo düzelt) 'tendyol' yerine 'trendyol'
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='products' AND COLUMN_NAME='sync_tendyol_status' AND TABLE_SCHEMA=DATABASE());
SET @sql := IF(@col>0, 'ALTER TABLE products CHANGE COLUMN sync_tendyol_status sync_trendyol_status ENUM("none","queued","ok","error") DEFAULT "none";', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @col2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='products' AND COLUMN_NAME='sync_tendyol_msg' AND TABLE_SCHEMA=DATABASE());
SET @sql2 := IF(@col2>0, 'ALTER TABLE products CHANGE COLUMN sync_tendyol_msg sync_trendyol_msg VARCHAR(255) NULL;', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;
