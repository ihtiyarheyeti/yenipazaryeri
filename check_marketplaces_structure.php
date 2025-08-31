<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Marketplaces tablosu yapısı kontrol ediliyor...\n\n";
    
    // Tablo yapısını göster
    $stmt = $pdo->query("DESCRIBE marketplaces");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== MARKETPLACES TABLO YAPISI ===\n";
    foreach ($columns as $col) {
        echo "Field: {$col['Field']} | Type: {$col['Type']} | Null: {$col['Null']} | Key: {$col['Key']} | Default: {$col['Default']}\n";
    }
    
    // Mevcut verileri göster
    $stmt = $pdo->query("SELECT * FROM marketplaces ORDER BY id");
    $marketplaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== MEVCUT VERİLER ===\n";
    foreach ($marketplaces as $mp) {
        echo "ID: {$mp['id']} | Name: {$mp['name']} | Slug: " . ($mp['slug'] ?? 'NULL') . " | Base URL: " . ($mp['base_url'] ?? 'NULL') . "\n";
    }
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
