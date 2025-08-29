<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=entegrasyon_paneli', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Test marketplace connections temizleniyor...\n";
    
    // Test connections'ları sil
    $sql = "DELETE FROM marketplace_connections WHERE id IN (5, 6, 7)";
    $pdo->exec($sql);
    echo "Test connections silindi!\n";
    
    // Sonucu kontrol et
    $stmt = $pdo->query("SELECT COUNT(*) FROM marketplace_connections");
    $count = $stmt->fetchColumn();
    echo "Kalan connections: $count\n";
    
    echo "\nTest connections temizlendi!\n";
    
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
