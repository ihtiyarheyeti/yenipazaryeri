USE yenipazaryeri;

-- Sadece eksik slug sütununu ekle
ALTER TABLE marketplaces 
ADD COLUMN slug VARCHAR(50) NOT NULL UNIQUE AFTER name;

-- Mevcut kayıtlar için slug değerlerini güncelle
UPDATE marketplaces SET slug = 'trendyol' WHERE id = 1;
UPDATE marketplaces SET slug = 'woocommerce' WHERE id = 2;

-- Kontrol et
SELECT * FROM marketplaces;
