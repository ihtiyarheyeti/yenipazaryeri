USE entegrasyon_paneli;

-- Mevcut marketplaces tablosunu kontrol et
SHOW TABLES LIKE 'marketplaces';

-- Eğer tablo varsa yapısını kontrol et
DESCRIBE marketplaces;

-- Eğer base_url kolonu yoksa ekle
ALTER TABLE marketplaces 
  ADD COLUMN IF NOT EXISTS base_url VARCHAR(500) NULL AFTER name;

-- Eğer created_at ve updated_at kolonları yoksa ekle
ALTER TABLE marketplaces 
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER base_url,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Eğer veri yoksa ekle
INSERT IGNORE INTO marketplaces (id,name,base_url) VALUES
 (1,'Trendyol','https://api.trendyol.com/sapigw'),
 (2,'WooCommerce','http://localhost/wp-json/wc/v3');

-- Son durumu kontrol et
SELECT * FROM marketplaces;
