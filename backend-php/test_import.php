<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'src/Config.php';
require 'src/Database.php';
require 'src/Context.php';
require 'src/Controllers/ProductImportController.php';

\App\Context::$tenantId = 1;

echo "Product Import Test\n";
echo "==================\n";

try {
    $pdo = \App\Database::pdo();
    echo "✓ Database connection: OK\n";
    
    // Test tenant ve user ekle
    $pdo->exec("INSERT IGNORE INTO tenants (id, name, slug, company_name, email) VALUES (1, 'Test Şirket', 'test', 'Test Şirket A.Ş.', 'test@example.com')");
    $pdo->exec("INSERT IGNORE INTO users (id, name, email, password, role) VALUES (1, 'Admin User', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')");
    
    // Test connection ekle
    $pdo->exec("INSERT IGNORE INTO connections (id, tenant_id, trendyol_supplier_id, trendyol_api_key, trendyol_api_secret, store_url, consumer_key, consumer_secret) VALUES (1, 1, '12345', 'test_key', 'test_secret', 'https://test.com', 'test_ck', 'test_cs')");
    
    echo "✓ Test data: OK\n";
    
    // Import controller'ı test et
    $controller = new \App\Controllers\ProductImportController();
    
    echo "\n--- Import Controller Test ---\n";
    echo "✓ Controller created: OK\n";
    
    // Database tablolarını kontrol et
    $tables = ['variants', 'product_marketplace_mapping', 'import_cursors'];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✓ Table $table: $count rows\n";
        } catch (Exception $e) {
            echo "✗ Table $table: ERROR - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ Import test completed!\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
