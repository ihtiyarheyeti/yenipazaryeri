<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Import cursors tablosu oluşturuluyor...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS import_cursors (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        marketplace_id INT NOT NULL,
        cursor_type VARCHAR(50) NOT NULL, -- 'products', 'orders', 'categories'
        external_cursor VARCHAR(500) NULL, -- JSON veya string cursor
        last_imported_at TIMESTAMP NULL,
        status ENUM('active', 'paused', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_tenant_mp_type (tenant_id, marketplace_id, cursor_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "Import cursors tablosu oluşturuldu!\n";
    
    // Test verisi ekle
    $sql = "INSERT IGNORE INTO import_cursors (tenant_id, marketplace_id, cursor_type, status) VALUES
            (1, 1, 'products', 'active'),
            (1, 1, 'orders', 'active'),
            (1, 2, 'products', 'active'),
            (1, 2, 'orders', 'active')";
    
    $pdo->exec($sql);
    echo "Test import cursors eklendi!\n";
    
    echo "\nImport cursors tablosu hazır!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
