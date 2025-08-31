<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Marketplace connections kontrol ediliyor...\n\n";
    
    // Marketplace connections
    $stmt = $pdo->query("SELECT * FROM marketplace_connections ORDER BY id DESC");
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== MARKETPLACE CONNECTIONS ===\n";
    foreach ($connections as $conn) {
        echo "ID: {$conn['id']}\n";
        echo "Tenant ID: {$conn['tenant_id']}\n";
        echo "Marketplace ID: {$conn['marketplace_id']}\n";
        echo "Base URL: {$conn['base_url']}\n";
        echo "API Key: " . substr($conn['api_key'], 0, 10) . "...\n";
        echo "Supplier ID: " . ($conn['supplier_id'] ?? 'NULL') . "\n";
        echo "Status: {$conn['status']}\n";
        echo "---\n";
    }
    
    // Marketplaces
    $stmt = $pdo->query("SELECT * FROM marketplaces ORDER BY id");
    $marketplaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== MARKETPLACES ===\n";
    foreach ($marketplaces as $mp) {
        echo "ID: {$mp['id']} | Name: {$mp['name']} | Base URL: {$mp['base_url']}\n";
    }
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
