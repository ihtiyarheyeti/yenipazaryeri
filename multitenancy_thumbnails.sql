USE yenipazaryeri;

-- Tenants tablosunda subdomain alanı
ALTER TABLE tenants
  ADD COLUMN IF NOT EXISTS subdomain VARCHAR(80) UNIQUE;

-- Örnek kayıt (yerel test için):
INSERT IGNORE INTO tenants (id,name,subdomain,created_at)
VALUES (1,'Default','app',NOW());

-- Product_images thumbnail alanları
ALTER TABLE tenants
  ADD COLUMN IF NOT EXISTS logo_url VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS theme_primary VARCHAR(16) NULL,
  ADD COLUMN IF NOT EXISTS theme_accent  VARCHAR(16) NULL,
  ADD COLUMN IF NOT EXISTS theme_mode    ENUM('light','dark') DEFAULT 'light';

-- Product_images zaten eklendi. Image sync log'u basitleştirmek için statü alanı:
ALTER TABLE product_images
  ADD COLUMN IF NOT EXISTS thumb_url VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS width INT NULL,
  ADD COLUMN IF NOT EXISTS height INT NULL,
  ADD COLUMN IF NOT EXISTS synced_to_ty  TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS synced_to_woo TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS last_synced_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS webp_url   VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS thumb_webp VARCHAR(255) NULL;
