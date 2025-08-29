<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Marketplace connections tablosu düzeltiliyor...\n";
    
    // supplier_id kolonu var mı kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM marketplace_connections LIKE 'supplier_id'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // supplier_id kolonu ekle
        $sql = "ALTER TABLE marketplace_connections ADD COLUMN supplier_id VARCHAR(255) AFTER api_secret";
        $pdo->exec($sql);
        echo "supplier_id kolonu eklendi!\n";
    } else {
        echo "supplier_id kolonu zaten mevcut!\n";
    }
    
    // Test verisi ekle
    $sql = "INSERT IGNORE INTO marketplace_connections (tenant_id, marketplace_id, base_url, api_key, api_secret, supplier_id, status) VALUES 
            (1, 1, 'https://api.trendyol.com', 'TY_KEY', 'TY_SECRET', 'SUPPLIER123', 'active'),
            (1, 2, 'https://your-woo-site.com/wp-json/wc/v3', 'CK_xxx', 'CS_xxx', NULL, 'active')";
    $pdo->exec($sql);
    echo "Test marketplace connections eklendi!\n";
    
    echo "\nMarketplace connections tablosu düzeltildi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
