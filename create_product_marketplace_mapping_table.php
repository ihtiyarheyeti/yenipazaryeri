<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Product marketplace mapping tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS product_marketplace_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        product_id INT NOT NULL,
        marketplace_id INT NOT NULL,
        external_id VARCHAR(255),
        external_sku VARCHAR(255),
        status ENUM('active', 'inactive', 'pending', 'failed') DEFAULT 'pending',
        sync_status ENUM('pending', 'syncing', 'synced', 'failed') DEFAULT 'pending',
        last_sync_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "Product marketplace mapping tablosu oluşturuldu!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
