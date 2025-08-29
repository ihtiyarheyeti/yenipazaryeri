<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Marketplace connections tablo yapısı kontrol ediliyor...\n\n";
    
    // Tablo yapısını göster
    $stmt = $pdo->query("DESCRIBE marketplace_connections");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== MARKETPLACE_CONNECTIONS TABLO YAPISI ===\n";
    foreach ($columns as $col) {
        echo "Field: {$col['Field']} | Type: {$col['Type']} | Null: {$col['Null']} | Key: {$col['Key']} | Default: {$col['Default']}\n";
    }
    
    // Mevcut verileri göster
    $stmt = $pdo->query("SELECT * FROM marketplace_connections ORDER BY id DESC");
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== MEVCUT VERİLER ===\n";
    foreach ($connections as $conn) {
        echo "ID: {$conn['id']} | Tenant ID: {$conn['tenant_id']} | Marketplace ID: {$conn['marketplace_id']} | Base URL: {$conn['base_url']} | Supplier ID: " . ($conn['supplier_id'] ?? 'NULL') . "\n";
    }
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
