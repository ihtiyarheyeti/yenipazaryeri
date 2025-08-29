<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Marketplace ID'leri düzeltiliyor...\n";
    
    // Önce mevcut verileri yedekle
    $stmt = $pdo->query("SELECT * FROM marketplaces ORDER BY id");
    $current = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Mevcut marketplaces:\n";
    foreach ($current as $mp) {
        echo "ID: {$mp['id']} | Name: {$mp['name']} | Base URL: {$mp['base_url']}\n";
    }
    
    // Marketplaces tablosunu temizle
    $pdo->exec("DELETE FROM marketplaces");
    echo "\nMarketplaces tablosu temizlendi!\n";
    
    // Doğru sırayla ekle
    $sql = "INSERT INTO marketplaces (id, name, base_url, created_at, updated_at) VALUES 
            (1, 'Trendyol', 'https://api.trendyol.com/sapigw', NOW(), NOW()),
            (2, 'WooCommerce', 'https://your-woo-site.com/wp-json/wc/v3', NOW(), NOW())";
    $pdo->exec($sql);
    echo "Doğru marketplace ID'leri eklendi!\n";
    
    // Mevcut marketplace_connections'ları güncelle
    $sql = "UPDATE marketplace_connections SET marketplace_id = CASE 
            WHEN marketplace_id = 1 THEN 2 
            WHEN marketplace_id = 2 THEN 1 
            ELSE marketplace_id END";
    $pdo->exec($sql);
    echo "Marketplace connections güncellendi!\n";
    
    // Sonucu kontrol et
    $stmt = $pdo->query("SELECT * FROM marketplaces ORDER BY id");
    $updated = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== GÜNCEL MARKETPLACES ===\n";
    foreach ($updated as $mp) {
        echo "ID: {$mp['id']} | Name: {$mp['name']} | Base URL: {$mp['base_url']}\n";
    }
    
    echo "\nMarketplace ID'leri düzeltildi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
