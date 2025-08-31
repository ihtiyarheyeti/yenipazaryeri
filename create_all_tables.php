<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Marketplace connections tablosu
    $sql = "CREATE TABLE IF NOT EXISTS marketplace_connections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        marketplace_id INT NOT NULL,
        name VARCHAR(255),
        base_url VARCHAR(500),
        api_key VARCHAR(255),
        api_secret VARCHAR(255),
        customer_id INT,
        store_name VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Marketplace connections tablosu oluşturuldu!\n";
    
    // Products tablosu
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        customer_id INT NOT NULL DEFAULT 1,
        name VARCHAR(500) NOT NULL,
        brand VARCHAR(255),
        description TEXT,
        category_path VARCHAR(500),
        seller_sku VARCHAR(255),
        origin_mp VARCHAR(50),
        origin_external_id VARCHAR(255),
        category_match VARCHAR(50),
        media_status VARCHAR(50),
        status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Products tablosu oluşturuldu!\n";
    
    // Variants tablosu
    $sql = "CREATE TABLE IF NOT EXISTS variants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        sku VARCHAR(255) NOT NULL,
        price DECIMAL(10,2),
        stock INT,
        attrs_json JSON,
        origin_mp VARCHAR(50),
        attrs_match VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Variants tablosu oluşturuldu!\n";
    
    // Jobs tablosu
    $sql = "CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(100) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'failed', 'dead') DEFAULT 'pending',
        payload JSON,
        attempts INT DEFAULT 0,
        max_attempts INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Jobs tablosu oluşturuldu!\n";
    
    echo "Tüm tablolar oluşturuldu!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
