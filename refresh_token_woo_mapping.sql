USE yenipazaryeri;

-- Refresh tokenlar
CREATE TABLE IF NOT EXISTS refresh_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  user_agent VARCHAR(255) NULL,
  ip VARCHAR(64) NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Woo varyant mapping (her variant için marketplace variation id)
CREATE TABLE IF NOT EXISTS variant_marketplace_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  variant_id INT NOT NULL,
  marketplace_id INT NOT NULL,         -- 2 = Woo
  external_variant_id VARCHAR(128) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_var_mp (variant_id, marketplace_id),
  FOREIGN KEY (variant_id) REFERENCES variants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products CSV için FT index (varsa atlar)
ALTER TABLE products ADD FULLTEXT KEY IF NOT EXISTS ft_products_name (name, brand, description);
