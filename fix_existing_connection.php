<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Mevcut connection düzeltiliyor...\n";
    
    // Mevcut connection'ı kontrol et
    $stmt = $pdo->query("SELECT * FROM marketplace_connections WHERE id = 4");
    $conn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conn) {
        echo "Mevcut connection:\n";
        echo "ID: {$conn['id']}\n";
        echo "Marketplace ID: {$conn['marketplace_id']}\n";
        echo "Base URL: {$conn['base_url']}\n";
        echo "API Key: " . substr($conn['api_key'], 0, 10) . "...\n";
        
        // Base URL WooCommerce gibi görünüyorsa, marketplace_id'yi 2 yap
        if (strpos($conn['base_url'], 'optimoon.com') !== false || strpos($conn['api_key'], 'ck_') !== false) {
            $sql = "UPDATE marketplace_connections SET marketplace_id = 2 WHERE id = 4";
            $pdo->exec($sql);
            echo "\nConnection WooCommerce olarak güncellendi!\n";
        }
    }
    
    // Sonucu kontrol et
    $stmt = $pdo->query("SELECT c.*, m.name as marketplace_name FROM marketplace_connections c JOIN marketplaces m ON m.id = c.marketplace_id WHERE c.id = 4");
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($updated) {
        echo "\nGüncel connection:\n";
        echo "ID: {$updated['id']}\n";
        echo "Marketplace: {$updated['marketplace_name']}\n";
        echo "Base URL: {$updated['base_url']}\n";
    }
    
    echo "\nConnection düzeltildi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
