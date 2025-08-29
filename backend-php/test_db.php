<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=entegrasyon_paneli;charset=utf8mb4', 'root', 'root');
    echo "Veritabanı bağlantısı başarılı\n";
    
    // Users tablosunu kontrol et
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users tablosunda " . $result['count'] . " kayıt var\n";
    
} catch (Exception $e) {
    echo "Veritabanı hatası: " . $e->getMessage() . "\n";
}
?>
