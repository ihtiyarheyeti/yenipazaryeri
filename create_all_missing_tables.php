<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Eksik tablolar oluşturuluyor...\n\n";
    
    // Options tablosu
    $sql = "CREATE TABLE IF NOT EXISTS options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        name VARCHAR(255) NOT NULL,
        display_name VARCHAR(255),
        type ENUM('select', 'radio', 'checkbox', 'text', 'textarea') DEFAULT 'select',
        required BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Options tablosu oluşturuldu!\n";
    
    // Option values tablosu
    $sql = "CREATE TABLE IF NOT EXISTS option_values (
        id INT AUTO_INCREMENT PRIMARY KEY,
        option_id INT NOT NULL,
        value VARCHAR(255) NOT NULL,
        display_name VARCHAR(255),
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Option values tablosu oluşturuldu!\n";
    
    // Product option values tablosu
    $sql = "CREATE TABLE IF NOT EXISTS product_option_values (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        option_id INT NOT NULL,
        option_value_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE,
        FOREIGN KEY (option_value_id) REFERENCES option_values(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Product option values tablosu oluşturuldu!\n";
    
    // Product images tablosu
    $sql = "CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        alt_text VARCHAR(255),
        sort_order INT DEFAULT 0,
        is_primary BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Product images tablosu oluşturuldu!\n";
    
    // Marketplaces tablosu
    $sql = "CREATE TABLE IF NOT EXISTS marketplaces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        api_version VARCHAR(20),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Marketplaces tablosu oluşturuldu!\n";
    
    // Category mappings tablosu
    $sql = "CREATE TABLE IF NOT EXISTS category_mappings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        marketplace_id INT NOT NULL,
        local_category VARCHAR(255) NOT NULL,
        external_category VARCHAR(255) NOT NULL,
        external_category_id VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Category mappings tablosu oluşturuldu!\n";
    
    // Audit logs tablosu
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        table_name VARCHAR(100),
        record_id INT,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Audit logs tablosu oluşturuldu!\n";
    
    // Policies tablosu
    $sql = "CREATE TABLE IF NOT EXISTS policies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        rules JSON,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Policies tablosu oluşturuldu!\n";
    
    // Orders tablosu
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        order_number VARCHAR(255) NOT NULL,
        marketplace_id INT,
        external_order_id VARCHAR(255),
        customer_name VARCHAR(255),
        customer_email VARCHAR(255),
        total_amount DECIMAL(10,2),
        status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Orders tablosu oluşturuldu!\n";
    
    // Test verileri ekle
    $sql = "INSERT IGNORE INTO marketplaces (name, slug, api_version, status) VALUES 
            ('WooCommerce', 'woocommerce', 'v3', 'active'),
            ('Trendyol', 'trendyol', 'v1', 'active')";
    $pdo->exec($sql);
    echo "Test marketplace verileri eklendi!\n";
    
    echo "\nTüm eksik tablolar oluşturuldu!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
