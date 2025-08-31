<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connection ID 8 detayları kontrol ediliyor...\n\n";
    
    // Connection detayları
    $stmt = $pdo->prepare("SELECT c.*, m.name as marketplace_name FROM marketplace_connections c JOIN marketplaces m ON m.id = c.marketplace_id WHERE c.id = 8");
    $stmt->execute();
    $conn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conn) {
        echo "=== CONNECTION DETAYLARI ===\n";
        echo "ID: {$conn['id']}\n";
        echo "Marketplace: {$conn['marketplace_name']} (ID: {$conn['marketplace_id']})\n";
        echo "Base URL: {$conn['base_url']}\n";
        echo "API Key: " . substr($conn['api_key'], 0, 10) . "...\n";
        echo "API Secret: " . substr($conn['api_secret'], 0, 10) . "...\n";
        echo "Supplier ID: " . ($conn['supplier_id'] ?? 'NULL') . "\n";
        echo "Status: {$conn['status']}\n";
        
        // Test URL'i oluştur
        $testUrl = rtrim($conn['base_url'], '/') . '/wp-json/wc/v3/products';
        echo "\nTest URL: $testUrl\n";
        
        // Basit HTTP test
        echo "\nBasit HTTP test yapılıyor...\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_NOBODY, true); // Sadece header'ları al
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        echo "HTTP Code: $httpCode\n";
        echo "Error: " . ($error ?: 'Yok') . "\n";
        echo "Response Time: " . round($totalTime * 1000, 2) . "ms\n";
        
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "✅ Bağlantı başarılı!\n";
        } else {
            echo "❌ HTTP Error: $httpCode\n";
        }
        
    } else {
        echo "Connection ID 8 bulunamadı!\n";
    }
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
