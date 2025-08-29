USE yenipazaryeri;

-- Mevcut marketplaces tablosunu sil (eğer varsa)
DROP TABLE IF EXISTS marketplaces;

-- Marketplaces tablosunu doğru yapıyla yeniden oluştur
CREATE TABLE marketplaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL, -- "Trendyol", "WooCommerce"
  slug VARCHAR(50) NOT NULL UNIQUE, -- "trendyol", "woocommerce"
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Temel marketplace'leri ekle
INSERT INTO marketplaces (id, name, slug) VALUES 
(1, 'Trendyol', 'trendyol'),
(2, 'WooCommerce', 'woocommerce');

-- Kontrol et
SELECT * FROM marketplaces;
