USE entegrasyon_paneli;

-- Eğer tablo varsa sil
DROP TABLE IF EXISTS marketplaces;

-- Marketplaces tablosunu yeniden oluştur
CREATE TABLE marketplaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  base_url VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Varsayılan marketplace'leri ekle
INSERT INTO marketplaces (id,name,base_url) VALUES
 (1,'Trendyol','https://api.trendyol.com/sapigw'),
 (2,'WooCommerce','http://localhost/wp-json/wc/v3');

-- Kontrol et
SELECT * FROM marketplaces;
