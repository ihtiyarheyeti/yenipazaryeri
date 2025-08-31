<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'src/Config.php';
require 'src/Database.php';
require 'src/Context.php';

\App\Context::$tenantId = 1;

echo "Dashboard Test\n";
echo "==============\n";

try {
    $pdo = \App\Database::pdo();
    echo "✓ Database connection: OK\n";
    
    // Dashboard metrics sorgularını test et
    $totProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE tenant_id=1")->fetchColumn();
    echo "✓ Products count: $totProducts\n";
    
    $totVariants = (int)$pdo->query("SELECT COUNT(*) FROM variants v JOIN products p ON p.id=v.product_id WHERE p.tenant_id=1")->fetchColumn();
    echo "✓ Variants count: $totVariants\n";
    
    $mappedTY = (int)$pdo->query("SELECT COUNT(*) FROM product_marketplace_mapping m JOIN products p ON p.id=m.product_id WHERE p.tenant_id=1 AND m.marketplace_id=1")->fetchColumn();
    echo "✓ Mapped TY: $mappedTY\n";
    
    $mappedWOO = (int)$pdo->query("SELECT COUNT(*) FROM products p JOIN product_marketplace_mapping m ON p.id=m.product_id WHERE p.tenant_id=1 AND m.marketplace_id=2")->fetchColumn();
    echo "✓ Mapped TY: $mappedWOO\n";
    
    $jobsPending = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='pending'")->fetchColumn();
    echo "✓ Jobs pending: $jobsPending\n";
    
    $jobsError = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='error'")->fetchColumn();
    echo "✓ Jobs error: $jobsError\n";
    
    $jobsDead = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='dead'")->fetchColumn();
    echo "✓ Jobs dead: $jobsDead\n";
    
    // Son logları test et
	$st = $pdo->prepare("SELECT id,level,status,message,created_at FROM logs ORDER BY id DESC LIMIT 5");
	$st->execute();
	$recentLogs = $st->fetchAll();
	echo "✓ Recent logs: " . count($recentLogs) . " found\n";
    
    echo "\n✓ Dashboard test completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
