-- Veritabanı durumu kontrol
USE yenipazaryeri;

-- Tablolar var mı?
SHOW TABLES;

-- Products tablosu içeriği
SELECT * FROM products;

-- Variants tablosu içeriği  
SELECT * FROM variants;

-- Marketplaces tablosu içeriği
SELECT * FROM marketplaces;

-- Ürün başına varyant sayısı
SELECT p.id, p.name, COUNT(v.id) as variant_count 
FROM products p 
LEFT JOIN variants v ON p.id = v.product_id 
GROUP BY p.id, p.name;
