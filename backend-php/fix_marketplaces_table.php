<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Marketplaces tablosu düzeltiliyor...\n";
    
    // base_url kolonu var mı kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM marketplaces LIKE 'base_url'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // base_url kolonu ekle
        $sql = "ALTER TABLE marketplaces ADD COLUMN base_url VARCHAR(500) AFTER name";
        $pdo->exec($sql);
        echo "base_url kolonu eklendi!\n";
    } else {
        echo "base_url kolonu zaten mevcut!\n";
    }
    
    // Marketplaces verilerini güncelle
    $sql = "UPDATE marketplaces SET base_url = CASE 
            WHEN id = 1 THEN 'https://your-woo-site.com/wp-json/wc/v3'
            WHEN id = 2 THEN 'https://api.trendyol.com/sapigw'
            ELSE base_url END";
    $pdo->exec($sql);
    echo "Marketplace base_url'leri güncellendi!\n";
    
    // Mevcut verileri göster
    $stmt = $pdo->query("SELECT * FROM marketplaces ORDER BY id");
    $marketplaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== GÜNCEL MARKETPLACES ===\n";
    foreach ($marketplaces as $mp) {
        echo "ID: {$mp['id']} | Name: {$mp['name']} | Base URL: {$mp['base_url']}\n";
    }
    
    echo "\nMarketplaces tablosu düzeltildi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
