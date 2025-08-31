<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Variant marketplace mapping tablosu oluşturuluyor...\n";
    
    // Variant mapping tablosu
    $sql = "CREATE TABLE IF NOT EXISTS variant_marketplace_mapping (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        variant_id INT NOT NULL,
        marketplace_id INT NOT NULL,
        external_product_id VARCHAR(128) NULL,
        external_variant_id VARCHAR(128) NULL,
        UNIQUE KEY uniq_variant_mp (variant_id, marketplace_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sql);
    echo "Variant marketplace mapping tablosu oluşturuldu!\n";
    
    // Performans için yardımcı index
    $sql = "CREATE INDEX IF NOT EXISTS idx_mp_extvar ON variant_marketplace_mapping (marketplace_id, external_variant_id)";
    $pdo->exec($sql);
    echo "Index oluşturuldu!\n";
    
    // Attribute mapping tablosu (autocomplete için)
    $sql = "CREATE TABLE IF NOT EXISTS attribute_mapping (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        marketplace_id INT NOT NULL,
        external_key VARCHAR(255) NOT NULL,
        external_value VARCHAR(255) NOT NULL,
        local_key VARCHAR(255) NOT NULL,
        local_value VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_attr_mp (tenant_id, marketplace_id, external_key, external_value)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sql);
    echo "Attribute mapping tablosu oluşturuldu!\n";
    
    echo "\nTüm mapping tabloları oluşturuldu!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
