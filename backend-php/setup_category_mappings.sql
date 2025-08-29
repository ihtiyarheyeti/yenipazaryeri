-- Kategori eşleştirme tablosu — yoksa oluştur
CREATE TABLE IF NOT EXISTS category_mappings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  marketplace_id INT NOT NULL,
  source_path VARCHAR(255) NOT NULL,  -- "Kadın>Takı>Bileklik" gibi
  external_category_id VARCHAR(64) NOT NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_tenant_mp_path (tenant_id, marketplace_id, source_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- marketplaces tablosunda id=1 Trendyol, id=2 WooCommerce olarak varsayalım.
-- marketplace_connections tablosunda şu kolonlar mevcut varsayıyoruz:
-- id, tenant_id, marketplace_id, api_key, api_secret, supplier_id, created_at, updated_at
