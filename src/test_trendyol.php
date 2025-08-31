<?php
// Router.php autoload için
require __DIR__ . '/Router.php';

// Gerekli class dosyalarını manuel require et
require __DIR__ . '/Utils/Http.php';
require __DIR__ . '/Utils/Rate.php';
require __DIR__ . '/Integrations/TrendyolAdapter.php';

use App\Integrations\TrendyolAdapter;

// 🔑 Buraya kendi Trendyol API bilgilerini girin
$conn = [
    'api_key'     => 'API_KEYINIZ',      // CVn4MItx2ORADdD5VLZI
    'api_secret'  => 'API_SECRETINIZ',   // btLhur2HrPmhKjXC0Fz9
    'supplier_id' => 'SUPPLIER_IDNIZ'    // 113278
];

// Adapter başlat
$trendyol = new TrendyolAdapter($conn);

echo "=== Trendyol API Test Başladı ===\n\n";

// 1. Ürün listesi çekme
echo "--- Ürün Listesi ---\n";
$products = $trendyol->listPriceInventory(null, 0, 10);
print_r($products);

// 2. Sipariş listesi (son 7 gün)
echo "\n--- Sipariş Listesi ---\n";
$start = date('c', strtotime('-7 days')); // ISO8601 format
$end   = date('c');
$orders = $trendyol->listOrders($start, $end, 0, 10);
print_r($orders);

echo "\n=== Test Bitti ===\n";
