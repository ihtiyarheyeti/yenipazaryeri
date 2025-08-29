<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Order tabloları oluşturuluyor...\n\n";
    
    // Order items tablosu
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        sku VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        attrs_json JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Order items tablosu oluşturuldu!\n";
    
    // Order marketplace mapping tablosu
    $sql = "CREATE TABLE IF NOT EXISTS order_marketplace_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        marketplace_id INT NOT NULL,
        external_id VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Order marketplace mapping tablosu oluşturuldu!\n";
    
    // Order status history tablosu
    $sql = "CREATE TABLE IF NOT EXISTS order_status_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        status VARCHAR(100) NOT NULL,
        note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Order status history tablosu oluşturuldu!\n";
    
    // Variant marketplace mapping tablosu
    $sql = "CREATE TABLE IF NOT EXISTS variant_marketplace_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        variant_id INT NOT NULL,
        marketplace_id INT NOT NULL,
        external_variant_id VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (variant_id) REFERENCES variants(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Variant marketplace mapping tablosu oluşturuldu!\n";
    
    // Test sipariş verisi ekle
    $sql = "INSERT IGNORE INTO orders (tenant_id, order_number, customer_name, customer_email, total_amount, status) VALUES 
            (1, 'ORD-001', 'Test Müşteri', 'test@example.com', 199.90, 'pending')";
    $pdo->exec($sql);
    echo "Test sipariş verisi eklendi!\n";
    
    // Test sipariş kalemi ekle
    $orderId = $pdo->lastInsertId() ?: 1;
    $sql = "INSERT IGNORE INTO order_items (order_id, sku, price, quantity) VALUES 
            ($orderId, 'TEST-SKU-001', 99.95, 2)";
    $pdo->exec($sql);
    echo "Test sipariş kalemi eklendi!\n";
    
    echo "\nTüm order tabloları oluşturuldu!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
