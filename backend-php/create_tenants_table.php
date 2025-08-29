<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tenants tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        domain VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Tenants tablosu oluşturuldu!\n";
    
    // Test tenant'ını ekle
    $sql = "INSERT INTO tenants (id, name, domain, status) VALUES 
            (1, 'Test Tenant', 'localhost', 'active')
            ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            domain = VALUES(domain),
            status = VALUES(status)";
    
    $pdo->exec($sql);
    echo "Test tenant eklendi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
