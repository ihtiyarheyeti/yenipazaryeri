USE yenipazaryeri;

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS status ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
  ADD COLUMN IF NOT EXISTS published_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL;

CREATE INDEX IF NOT EXISTS idx_products_tenant_status ON products(tenant_id,status);
CREATE INDEX IF NOT EXISTS idx_products_published_at ON products(published_at);
