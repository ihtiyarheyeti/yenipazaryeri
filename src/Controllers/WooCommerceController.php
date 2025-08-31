<?php
namespace App\Controllers;

use App\Database;

final class WooCommerceController {
    public function listProducts(array $ctx): array {
        try {
            $pdo = Database::pdo();
            
            // Aktif WooCommerce bağlantısını marketplace_id = 2 ile bul
            $stmt = $pdo->prepare("SELECT * FROM marketplace_connections 
                                   WHERE marketplace_id = 2 
                                     AND base_url IS NOT NULL 
                                     AND api_key IS NOT NULL 
                                     AND api_secret IS NOT NULL 
                                   ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $conn = $stmt->fetch();

            if (!$conn) {
                return ['ok' => false, 'error' => 'WooCommerce bağlantısı bulunamadı (marketplace_id=2)'];
            }

            $importedCount = 0;
            $updatedCount = 0;
            $page = 1;
            $perPage = 50;

            do {
                // WooCommerce REST API'den ürünleri çek
                $url = rtrim($conn['base_url'], '/') . '/wp-json/wc/v3/products';
                $url .= '?per_page=' . $perPage . '&page=' . $page;
                
                $auth = base64_encode($conn['api_key'] . ':' . $conn['api_secret']);
                $headers = [
                    'Authorization: Basic ' . $auth,
                    'Content-Type: application/json',
                    'User-Agent: Yenipazaryeri/1.0'
                ];

                // cURL ile API çağrısı
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    return [
                        'ok' => false, 
                        'error' => 'cURL hatası: ' . $error
                    ];
                }

                if ($httpCode !== 200) {
                    return [
                        'ok' => false, 
                        'error' => 'WooCommerce API hatası: HTTP ' . $httpCode
                    ];
                }

                $products = json_decode($response, true);
                if (!is_array($products)) {
                    return [
                        'ok' => false, 
                        'error' => 'Geçersiz JSON yanıtı'
                    ];
                }

                if (empty($products)) {
                    break; // Sayfa boşsa döngüyü bitir
                }

                // Her ürünü işle
                foreach ($products as $product) {
                    $result = $this->processProduct($pdo, $conn['id'], $product);
                    if ($result === 'imported') {
                        $importedCount++;
                    } elseif ($result === 'updated') {
                        $updatedCount++;
                    }
                }

                $page++;
                
                // Rate limiting - her sayfa arasında kısa bekleme
                if (count($products) === $perPage) {
                    usleep(100000); // 0.1 saniye bekle
                }

            } while (count($products) === $perPage);

            return [
                'ok' => true,
                'imported_count' => $importedCount,
                'updated_count' => $updatedCount,
                'total_processed' => $importedCount + $updatedCount
            ];

        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => 'WooCommerce import hatası: ' . $e->getMessage()
            ];
        }
    }

    private function processProduct(\PDO $pdo, int $connectionId, array $product): string {
        try {
            $pdo->beginTransaction();

            // Ürün zaten var mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM products 
                                   WHERE origin_mp = 'woo' 
                                     AND origin_external_id = ?");
            $stmt->execute([$product['id']]);
            $existingProduct = $stmt->fetch();

            $productData = [
                'name' => $product['name'] ?? '',
                'description' => $product['description'] ?? '',
                'origin_mp' => 'woo',
                'origin_external_id' => $product['id'],
                'connection_id' => $connectionId,
                'thumbnail_url' => null
            ];

            // İlk resmi thumbnail olarak al
            if (!empty($product['images']) && isset($product['images'][0]['src'])) {
                $productData['thumbnail_url'] = $product['images'][0]['src'];
            }

            if ($existingProduct) {
                // Ürünü güncelle
                $stmt = $pdo->prepare("UPDATE products SET 
                                       name = ?, description = ?, thumbnail_url = ?, 
                                       updated_at = NOW() 
                                       WHERE id = ?");
                $stmt->execute([
                    $productData['name'],
                    $productData['description'],
                    $productData['thumbnail_url'],
                    $existingProduct['id']
                ]);
                $productId = $existingProduct['id'];
                $result = 'updated';
            } else {
                // Yeni ürün ekle
                $stmt = $pdo->prepare("INSERT INTO products 
                                       (name, description, origin_mp, origin_external_id, 
                                        connection_id, thumbnail_url, created_at, updated_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $productData['name'],
                    $productData['description'],
                    $productData['origin_mp'],
                    $productData['origin_external_id'],
                    $productData['connection_id'],
                    $productData['thumbnail_url']
                ]);
                $productId = $pdo->lastInsertId();
                $result = 'imported';
            }

            // Varyant bilgilerini işle (sadece ana ürün varyantı)
            $this->processVariants($pdo, $productId, $product);

            $pdo->commit();
            return $result;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function processVariants(\PDO $pdo, int $productId, array $product): void {
        // Mevcut varyantları sil (yeniden oluşturmak için)
        $stmt = $pdo->prepare("DELETE FROM variants WHERE product_id = ?");
        $stmt->execute([$productId]);

        // Ana ürün varyantı
        $variantData = [
            'product_id' => $productId,
            'sku' => $product['sku'] ?? '',
            'price' => $product['price'] ?? 0,
            'stock_quantity' => $product['stock_quantity'] ?? 0,
            'is_active' => $product['status'] === 'publish' ? 1 : 0
        ];

        $stmt = $pdo->prepare("INSERT INTO variants 
                               (product_id, sku, price, stock_quantity, is_active, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $variantData['product_id'],
            $variantData['sku'],
            $variantData['price'],
            $variantData['stock_quantity'],
            $variantData['is_active']
        ]);

        // Variations desteği şimdilik pasif - sadece ana ürün varyantı kaydedildi
    }
}
