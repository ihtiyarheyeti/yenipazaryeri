<?php
namespace App\Controllers;

use App\Integrations\WooAdapter;
use App\Database;

final class WooCommerceController {
    public function listProducts(array $ctx): array {
        try {
            // Aktif WooCommerce connection’ı DB’den bul
            $pdo = Database::pdo();
            $stmt = $pdo->query("SELECT * FROM connections 
                                 WHERE woo_site_url IS NOT NULL 
                                   AND woo_consumer_key IS NOT NULL 
                                   AND woo_consumer_secret IS NOT NULL 
                                 LIMIT 1");
            $conn = $stmt->fetch();

            if (!$conn) {
                return ['ok' => false, 'error' => 'WooCommerce bağlantısı bulunamadı'];
            }

            $adapter = new WooAdapter([
                'base_url'       => $conn['woo_site_url'],
                'api_key'        => $conn['woo_consumer_key'],
                'api_secret'     => $conn['woo_consumer_secret'],
            ]);

            // Woo’dan ürünleri çek
            $products = $adapter->listProducts(1, 20);

            return [
                'ok' => true,
                'count' => count($products),
                'products' => $products
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Woo ürünleri alınamadı',
                'detail' => $e->getMessage()
            ];
        }
    }
}
