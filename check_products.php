<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ürünler tablosundaki veri sayısını kontrol et
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM products');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Ürünler tablosunda toplam: $count kayıt var\n\n";
    
    if ($count > 0) {
        // İlk 5 ürünü göster
        $stmt = $pdo->query('SELECT id, name, brand, status FROM products LIMIT 5');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "İlk 5 ürün:\n";
        foreach ($products as $product) {
            echo "- ID: {$product['id']}, İsim: {$product['name']}, Marka: {$product['brand']}, Durum: {$product['status']}\n";
        }
    } else {
        echo "Ürünler tablosunda hiç veri yok!\n";
        
        // Test ürünü ekle
        $sql = "INSERT INTO products (tenant_id, customer_id, name, brand, description, status) VALUES 
                (1, 1, 'Test Ürün 1', 'Test Marka', 'Bu bir test ürünüdür', 'active')";
        $pdo->exec($sql);
        echo "Test ürünü eklendi!\n";
    }
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
