-- Yenipazaryeri veritabanı tabloları
USE yenipazaryeri;

-- Marketplaces tablosu
CREATE TABLE IF NOT EXISTS marketplaces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  base_url VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products tablosu
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL DEFAULT 1,
  name VARCHAR(255) NOT NULL,
  brand VARCHAR(255),
  description TEXT,
  category_path JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Variants tablosu
CREATE TABLE IF NOT EXISTS variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  sku VARCHAR(100),
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock INT NOT NULL DEFAULT 0,
  attrs JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Örnek veriler
INSERT IGNORE INTO marketplaces (id,name,base_url) VALUES
 (1,'Trendyol','https://api.trendyol.com'),
 (2,'WooCommerce','http://localhost/wp-json/wc/v3/products');

-- Örnek ürünler
INSERT INTO products (tenant_id,name,brand,description,category_path,created_at,updated_at)
VALUES
 (1,'Kristal Bileklik','Optimon','Şeffaf kristal bileklik', JSON_ARRAY('Kadın','Takı','Bileklik'), NOW(), NOW()),
 (1,'Doğal Taş Kolye','Optimon','Yeşim taşı kolye',      JSON_ARRAY('Kadın','Takı','Kolye'),     NOW(), NOW()),
 (1,'Gümüş Yüzük','Optimon','925 ayar gümüş yüzük',       JSON_ARRAY('Kadın','Takı','Yüzük'),     NOW(), NOW());

-- Örnek varyantlar
INSERT INTO variants (product_id,sku,price,stock,attrs,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-S'), 199.90, 10, JSON_OBJECT('Renk','Gümüş','Beden','S'), NOW(), NOW() FROM products p WHERE p.name='Kristal Bileklik';

INSERT INTO variants (product_id,sku,price,stock,attrs,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-M'), 209.90, 12, JSON_OBJECT('Renk','Gümüş','Beden','M'), NOW(), NOW() FROM products p WHERE p.name='Kristal Bileklik';

INSERT INTO variants (product_id,sku,price,stock,attrs,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-STD'), 349.00, 5, JSON_OBJECT('Taş','Yeşim'), NOW(), NOW() FROM products p WHERE p.name='Doğal Taş Kolye';

INSERT INTO variants (product_id,sku,price,stock,attrs,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-6'),  499.00, 8,  JSON_OBJECT('Ölçü','6'), NOW(), NOW() FROM products p WHERE p.name='Gümüş Yüzük';

INSERT INTO variants (product_id,sku,price,stock,attrs,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-7'),  499.00, 6,  JSON_OBJECT('Ölçü','7'), NOW(), NOW() FROM products p WHERE p.name='Gümüş Yüzük';

-- Görünüm: ürün başına varyant sayısı
DROP VIEW IF EXISTS v_products_with_counts;
CREATE VIEW v_products_with_counts AS
SELECT p.*, 
       (SELECT COUNT(*) FROM variants v WHERE v.product_id=p.id) AS variant_count
FROM products p;
