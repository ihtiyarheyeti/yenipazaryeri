USE yenipazaryeri;

-- Marketplaces tablosunu kontrol et
SHOW TABLES LIKE 'marketplaces';

-- Marketplaces tablosu yapısını kontrol et
DESCRIBE marketplaces;

-- Marketplaces verilerini kontrol et
SELECT * FROM marketplaces;

-- Eğer tablo yoksa oluştur
CREATE TABLE IF NOT EXISTS marketplaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  base_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Eğer veri yoksa ekle
INSERT IGNORE INTO marketplaces (id,name,base_url) VALUES
 (1,'Trendyol','https://api.trendyol.com/sapigw'),
 (2,'WooCommerce','http://localhost/wp-json/wc/v3');

-- Kontrol et
SELECT * FROM marketplaces;
