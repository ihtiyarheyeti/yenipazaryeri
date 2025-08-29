<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'src/Config.php';
require 'src/Database.php';

echo "Tablo Yapı Kontrolü\n";
echo "===================\n";

try {
    $pdo = \App\Database::pdo();
    
    // Önemli tabloların yapısını kontrol et
    $importantTables = ['products', 'connections', 'tenants', 'users'];
    
    foreach ($importantTables as $table) {
        echo "\n--- $table tablosu ---\n";
        try {
            $columns = $pdo->query("DESCRIBE $table")->fetchAll();
            foreach ($columns as $col) {
                echo "- {$col['Field']}: {$col['Type']} " . 
                     ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
                     ($col['Key'] ? " ({$col['Key']})" : '') . "\n";
            }
        } catch (Exception $e) {
            echo "✗ HATA: " . $e->getMessage() . "\n";
        }
    }
    
    // Mevcut marketplace bağlantılarını kontrol et
    echo "\n--- Marketplace Bağlantıları ---\n";
    try {
        $connections = $pdo->query("SELECT * FROM connections LIMIT 5")->fetchAll();
        echo "Bağlantı sayısı: " . count($connections) . "\n";
        if (!empty($connections)) {
            foreach ($connections as $conn) {
                echo "- ID: {$conn['id']}, Marketplace: {$conn['marketplace_name']}, Status: {$conn['status']}\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ HATA: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ HATA: " . $e->getMessage() . "\n";
}
?>
