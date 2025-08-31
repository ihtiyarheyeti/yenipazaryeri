<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // password kelimesinin hash'ini oluştur
    $password = 'password';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "Password: $password\n";
    echo "Hash: $hash\n";
    
    // Users tablosunu güncelle
    $sql = "UPDATE users SET password_hash = ? WHERE email = 'test@example.com'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hash]);
    
    echo "Password hash güncellendi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
