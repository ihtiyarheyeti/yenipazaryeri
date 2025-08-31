<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'src/Config.php';
require 'src/Database.php';

echo "SQL Dosyası Çalıştırılıyor...\n";
echo "=============================\n";

try {
    $pdo = \App\Database::pdo();
    echo "✓ Veritabanı bağlantısı: OK\n";
    
    // SQL dosyasını oku
    $sql = file_get_contents('create_missing_tables.sql');
    if (!$sql) {
        throw new Exception("SQL dosyası okunamadı");
    }
    
    // SQL komutlarını böl
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "\nToplam " . count($statements) . " SQL komutu çalıştırılacak...\n";
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $i => $statement) {
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            echo "✓ Komut " . ($i + 1) . " başarılı\n";
            $success++;
        } catch (Exception $e) {
            echo "✗ Komut " . ($i + 1) . " HATA: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\nSonuç:\n";
    echo "✓ Başarılı: $success\n";
    echo "✗ Hatalı: $errors\n";
    
    if ($errors === 0) {
        echo "\n🎉 Tüm tablolar başarıyla oluşturuldu!\n";
    } else {
        echo "\n⚠️ Bazı hatalar oluştu. Lütfen kontrol edin.\n";
    }
    
} catch (Exception $e) {
    echo "✗ HATA: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
