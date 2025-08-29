<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Slug kolonunu ekle
    $sql = "ALTER TABLE tenants ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NOT NULL DEFAULT 'default' AFTER name";
    $pdo->exec($sql);
    echo "Slug kolonu eklendi!\n";
    
    // Test tenant'ına slug ekle
    $sql = "UPDATE tenants SET slug = 'default' WHERE id = 1";
    $pdo->exec($sql);
    echo "Test tenant slug güncellendi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
