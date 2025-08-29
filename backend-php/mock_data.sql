USE entegrasyon_paneli;

-- Mock ürünler ekle (eğer yoksa)
INSERT IGNORE INTO products (tenant_id,name,brand,description,category_path,status,created_at,updated_at)
VALUES
 (1,'Kristal Bileklik','Optimon','Şeffaf kristal bileklik', JSON_ARRAY('Kadın','Takı','Bileklik'), 'active', NOW(), NOW()),
 (1,'Doğal Taş Kolye','Optimon','Yeşim taşı kolye',      JSON_ARRAY('Kadın','Takı','Kolye'),     'active', NOW(), NOW()),
 (1,'Gümüş Yüzük','Optimon','925 ayar gümüş yüzük',       JSON_ARRAY('Kadın','Takı','Yüzük'),     'active', NOW(), NOW()),
 (1,'Altın Kolye','Optimon','14 ayar altın kolye',         JSON_ARRAY('Kadın','Takı','Kolye'),     'active', NOW(), NOW()),
 (1,'İnci Küpe','Optimon','Doğal inci küpe',               JSON_ARRAY('Kadın','Takı','Küpe'),      'active', NOW(), NOW());

-- Mock varyantlar ekle
INSERT IGNORE INTO variants (product_id,sku,price,stock,attrs_json,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-S'), 199.90, 10, JSON_OBJECT('Renk','Gümüş','Beden','S'), NOW(), NOW() FROM products p WHERE p.name='Kristal Bileklik';

INSERT IGNORE INTO variants (product_id,sku,price,stock,attrs_json,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-M'), 209.90, 12, JSON_OBJECT('Renk','Gümüş','Beden','M'), NOW(), NOW() FROM products p WHERE p.name='Kristal Bileklik';

INSERT IGNORE INTO variants (product_id,sku,price,stock,attrs_json,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-STD'), 349.00, 5, JSON_OBJECT('Taş','Yeşim'), NOW(), NOW() FROM products p WHERE p.name='Doğal Taş Kolye';

INSERT IGNORE INTO variants (product_id,sku,price,stock,attrs_json,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-6'),  499.00, 8,  JSON_OBJECT('Ölçü','6'), NOW(), NOW() FROM products p WHERE p.name='Gümüş Yüzük';

INSERT IGNORE INTO variants (product_id,sku,price,stock,attrs_json,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-7'),  499.00, 6,  JSON_OBJECT('Ölçü','7'), NOW(), NOW() FROM products p WHERE p.name='Gümüş Yüzük';

INSERT IGNORE INTO variants (product_id,sku,price,stock,attrs_json,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-18'), 899.00, 3,  JSON_OBJECT('Renk','Sarı Altın'), NOW(), NOW() FROM products p WHERE p.name='Altın Kolye';

INSERT IGNORE INTO variants (product_id,sku,price,stock,attrs_json,created_at,updated_at)
SELECT p.id, CONCAT('SKU-',p.id,'-WHITE'), 299.00, 7,  JSON_OBJECT('Renk','Beyaz'), NOW(), NOW() FROM products p WHERE p.name='İnci Küpe';

-- Marketplaces tablosunu güncelle
INSERT IGNORE INTO marketplaces (id,name,base_url) VALUES
 (1,'Trendyol','https://api.trendyol.com/sapigw'),
 (2,'WooCommerce','http://localhost/wp-json/wc/v3');

-- Sync status alanlarını ekle (eğer yoksa)
ALTER TABLE products 
  ADD COLUMN IF NOT EXISTS sync_woo_status ENUM('none','queued','ok','error') DEFAULT 'none',
  ADD COLUMN IF NOT EXISTS sync_trendyol_status ENUM('none','queued','ok','error') DEFAULT 'none',
  ADD COLUMN IF NOT EXISTS sync_woo_msg VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS sync_trendyol_msg VARCHAR(255) NULL;

-- Status alanını ekle (eğer yoksa)
ALTER TABLE products 
  ADD COLUMN IF NOT EXISTS status ENUM('draft','active','archived') DEFAULT 'draft',
  ADD COLUMN IF NOT EXISTS review_status ENUM('pending','approved','rejected') DEFAULT 'pending';
