USE entegrasyon_paneli;

-- Connections tablosuna eksik kolonları ekle
ALTER TABLE connections 
ADD COLUMN IF NOT EXISTS marketplace_id INT NULL AFTER tenant_id,
ADD COLUMN IF NOT EXISTS marketplace_name VARCHAR(50) NULL AFTER marketplace_id,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'error') DEFAULT 'active' AFTER consumer_secret;

-- Mevcut bağlantıları güncelle
UPDATE connections SET 
  marketplace_id = CASE 
    WHEN trendyol_supplier_id IS NOT NULL THEN 1 
    WHEN woo_site_url IS NOT NULL THEN 2 
    ELSE NULL 
  END,
  marketplace_name = CASE 
    WHEN trendyol_supplier_id IS NOT NULL THEN 'trendyol'
    WHEN woo_site_url IS NOT NULL THEN 'woocommerce'
    ELSE 'unknown'
  END
WHERE marketplace_id IS NULL;

-- Index ekle
ALTER TABLE connections 
ADD INDEX idx_marketplace (marketplace_id, status),
ADD INDEX idx_tenant_marketplace (tenant_id, marketplace_id);

-- Test verisi ekle (eğer yoksa)
INSERT IGNORE INTO connections (
  tenant_id, marketplace_id, marketplace_name, 
  trendyol_supplier_id, trendyol_api_key, trendyol_api_secret,
  woo_site_url, woo_consumer_key, woo_consumer_secret,
  store_url, consumer_key, consumer_secret, status
) VALUES (
  1, 1, 'trendyol',
  '12345', 'test_key', 'test_secret',
  NULL, NULL, NULL,
  'https://test.com', 'test_ck', 'test_cs', 'active'
);

INSERT IGNORE INTO connections (
  tenant_id, marketplace_id, marketplace_name,
  trendyol_supplier_id, trendyol_api_key, trendyol_api_secret,
  woo_site_url, woo_consumer_key, woo_consumer_secret,
  store_url, consumer_key, consumer_secret, status
) VALUES (
  1, 2, 'woocommerce',
  NULL, NULL, NULL,
  'https://test-woo.com', 'test_woo_ck', 'test_woo_cs',
  'https://test-woo.com', 'test_woo_ck', 'test_woo_cs', 'active'
);
