<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Önce mevcut tabloyu sil
    $pdo->exec("DROP TABLE IF EXISTS tenants");
    
    // Yeni tabloyu oluştur
    $sql = "CREATE TABLE tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL DEFAULT 'default',
        domain VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Tenants tablosu yeniden oluşturuldu!\n";
    
    // Test tenant'ını ekle
    $sql = "INSERT INTO tenants (id, name, slug, domain, status) VALUES 
            (1, 'Test Tenant', 'default', 'localhost', 'active')";
    
    $pdo->exec($sql);
    echo "Test tenant eklendi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
