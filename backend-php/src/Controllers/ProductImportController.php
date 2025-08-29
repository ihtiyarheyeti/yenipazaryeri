<?php
namespace App\Controllers;

use App\Database;
use App\Integrations\TrendyolAdapter;
use App\Integrations\WooAdapter;

final class ProductImportController {
    
    /**
     * Trendyol'dan ürünleri çek ve local DB'ye kaydet
     */
    public function pullTrendyol(array $p, array $b, array $q): array {
        try {
            $tenantId = \App\Context::$tenantId;
            
            // Trendyol bağlantısını bul
            $stmt = Database::pdo()->prepare("
                SELECT * FROM marketplace_connections 
                WHERE tenant_id = ? AND marketplace_id = 1 AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$tenantId]);
            $conn = $stmt->fetch();
            
            if (!$conn) {
                return ['ok' => false, 'error' => 'Trendyol bağlantısı bulunamadı'];
            }
            
            $ty = new TrendyolAdapter($conn);
            
            // Import cursor'ı kontrol et
            $stmt = Database::pdo()->prepare("
                SELECT * FROM import_cursors 
                WHERE tenant_id = ? AND marketplace_id = 1 AND cursor_key = 'products'
                LIMIT 1
            ");
            $stmt->execute([$tenantId]);
            $cursor = $stmt->fetch();
            
            if (!$cursor) {
                // Yeni cursor oluştur
                Database::pdo()->prepare("
                    INSERT INTO import_cursors (tenant_id, marketplace_id, cursor_key, page, last_run)
                    VALUES (?, 1, 'products', 0, NOW())
                ")->execute([$tenantId]);
                $page = 0;
            } else {
                $page = $cursor['page'];
            }
            
            $imported = 0;
            $updated = 0;
            
            // Ürünleri sayfa sayfa çek
            do {
                $products = $ty->listProducts($page, 100);
                
                if (empty($products['content'])) {
                    break;
                }
                
                foreach ($products['content'] as $product) {
                    $result = $this->upsertProduct($product, $tenantId, 'trendyol');
                    if ($result['action'] === 'created') $imported++;
                    if ($result['action'] === 'updated') $updated++;
                }
                
                $page++;
                
                // Cursor'ı güncelle
                Database::pdo()->prepare("
                    UPDATE import_cursors 
                    SET page = ?, last_run = NOW() 
                    WHERE tenant_id = ? AND marketplace_id = 1 AND cursor_key = 'products'
                ")->execute([$page, $tenantId]);
                
            } while (count($products['content']) === 100);
            
            return [
                'ok' => true,
                'imported' => $imported,
                'updated' => $updated,
                'total_pages' => $page
            ];
            
        } catch (\Exception $e) {
            error_log("ProductImport::pullTrendyol ERROR: " . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * WooCommerce'dan ürünleri çek ve local DB'ye kaydet
     */
    public function pullWooCommerce(array $p, array $b, array $q): array {
        try {
            $tenantId = \App\Context::$tenantId;
            
            // WooCommerce bağlantısını bul
            $stmt = Database::pdo()->prepare("
                SELECT * FROM marketplace_connections 
                WHERE tenant_id = ? AND marketplace_id = 2 AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$tenantId]);
            $conn = $stmt->fetch();
            
            if (!$conn) {
                return ['ok' => false, 'error' => 'WooCommerce bağlantısı bulunamadı'];
            }
            
            $woo = new WooAdapter($conn);
            
            // Import cursor'ı kontrol et
            $stmt = Database::pdo()->prepare("
                SELECT * FROM import_cursors 
                WHERE tenant_id = ? AND marketplace_id = 2 AND cursor_key = 'products'
                LIMIT 1
            ");
            $stmt->execute([$tenantId]);
            $cursor = $stmt->fetch();
            
            if (!$cursor) {
                // Yeni cursor oluştur
                Database::pdo()->prepare("
                    INSERT INTO import_cursors (tenant_id, marketplace_id, cursor_key, page, last_run)
                    VALUES (?, 2, 'products', 1, NOW())
                ")->execute([$tenantId]);
                $page = 1;
            } else {
                $page = $cursor['page'] ?: 1; // Eğer 0 ise 1 yap
            }
            
            $imported = 0;
            $updated = 0;
            
            // Ürünleri sayfa sayfa çek
            do {
                $products = $woo->listProducts($page, 100);
                
                if (empty($products)) {
                    break;
                }
                
                foreach ($products as $product) {
                    $result = $this->upsertProduct($product, $tenantId, 'woocommerce');
                    if ($result['action'] === 'created') $imported++;
                    if ($result['action'] === 'updated') $updated++;
                }
                
                $page++;
                
                // Cursor'ı güncelle
                Database::pdo()->prepare("
                    UPDATE import_cursors 
                    SET page = ?, last_run = NOW() 
                    WHERE tenant_id = ? AND marketplace_id = 2 AND cursor_key = 'products'
                ")->execute([$page, $tenantId]);
                
            } while (count($products) === 100);
            
            return [
                'ok' => true,
                'imported' => $imported,
                'updated' => $updated,
                'total_pages' => $page
            ];
            
        } catch (\Exception $e) {
            error_log("ProductImport::pullWooCommerce ERROR: " . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * CSV dosyasından ürünleri import et
     */
    public function importFromCsv(array $p, array $b, array $q): array {
        try {
            $tenantId = \App\Context::$tenantId;
            
            if (!isset($b['csv_file']) || !is_uploaded_file($b['csv_file']['tmp_name'])) {
                return ['ok' => false, 'error' => 'CSV dosyası yüklenmedi'];
            }
            
            $csvFile = $b['csv_file']['tmp_name'];
            $handle = fopen($csvFile, 'r');
            
            if (!$handle) {
                return ['ok' => false, 'error' => 'CSV dosyası açılamadı'];
            }
            
            // Header'ı oku
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return ['ok' => false, 'error' => 'CSV header okunamadı'];
            }
            
            $imported = 0;
            $errors = 0;
            $row = 2; // 1. satır header
            
            while (($data = fgetcsv($handle)) !== false) {
                try {
                    $productData = array_combine($headers, $data);
                    $this->upsertProductFromCsv($productData, $tenantId);
                    $imported++;
                } catch (\Exception $e) {
                    error_log("CSV Import Row $row ERROR: " . $e->getMessage());
                    $errors++;
                }
                $row++;
            }
            
            fclose($handle);
            
            return [
                'ok' => true,
                'imported' => $imported,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            error_log("ProductImport::importFromCsv ERROR: " . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Trendyol ürünlerini upsert et
     */
    private function upsertTrendyol(array $items): int {
        $pdo = Database::pdo();
        $tenant = \App\Context::$tenantId;
        $n = 0;
        
        foreach ($items as $it) {
            $name = trim($it['title'] ?? $it['name'] ?? '');
            if (!$name) continue;
            
            $brand = $it['brand'] ?? '';
            $desc = $it['description'] ?? '';
            $catId = $it['categoryId'] ?? null;
            
            $pid = $this->findOrCreateProduct($tenant, $name, $brand, $desc, null);
            
            // Kategori eşleşmesi
            $match = 'unmapped';
            if ($catId) {
                $st = $pdo->prepare("SELECT local_path FROM category_mapping WHERE tenant_id = ? AND marketplace_id = 1 AND external_id = ?");
                $st->execute([$tenant, $catId]);
                $local = $st->fetchColumn();
                if ($local) {
                    $match = 'mapped';
                    $pdo->prepare("UPDATE products SET category_path = ?, category_match = ? WHERE id = ?")
                        ->execute([$local, $match, $pid]);
                } else {
                    $pdo->prepare("UPDATE products SET category_match = ? WHERE id = ?")->execute([$match, $pid]);
                }
            }
            
            // Varyant
            $sku = $it['barcode'] ?? ($it['sku'] ?? null);
            $price = $it['listPrice'] ?? ($it['price'] ?? null);
            $stock = $it['quantity'] ?? ($it['stock'] ?? null);
            $attrs = [];
            if (!empty($it['attributes'])) {
                foreach ($it['attributes'] as $a) {
                    if (isset($a['name'], $a['value'])) {
                        $attrs[$a['name']] = $a['value'];
                    }
                }
            }
            $vid = $this->upsertVariant($pid, $sku, $price, $stock, $attrs);
            
            // Attribute eşleşmesi kontrol
            $vmatch = 'unmapped';
            if ($attrs) {
                $st = $pdo->prepare("SELECT external_key FROM attribute_mapping WHERE tenant_id = ? AND marketplace_id = 1");
                $st->execute([$tenant]);
                $keys = $st->fetchAll(\PDO::FETCH_COLUMN);
                if ($keys) {
                    $vmatch = 'mapped';
                }
            }
            $pdo->prepare("UPDATE variants SET attrs_match = ? WHERE id = ?")->execute([$vmatch, $vid]);
            
            $n++;
        }
        return $n;
    }
    
    /**
     * WooCommerce ürünlerini upsert et
     */
    private function upsertWoo(array $items): int {
        $pdo = Database::pdo();
        $tenant = \App\Context::$tenantId;
        $n = 0;
        
        foreach ($items as $it) {
            $name = trim($it['name'] ?? $it['title'] ?? '');
            if (!$name) continue;
            
            $brand = $it['brand'] ?? '';
            $desc = $it['description'] ?? '';
            $catId = $it['category_id'] ?? null;
            
            $pid = $this->findOrCreateProduct($tenant, $name, $brand, $desc, null);
            
            // Kategori eşleşmesi
            $match = 'unmapped';
            if ($catId) {
                $st = $pdo->prepare("SELECT local_path FROM category_mapping WHERE tenant_id = ? AND marketplace_id = 2 AND external_id = ?");
                $st->execute([$tenant, $catId]);
                $local = $st->fetchColumn();
                if ($local) {
                    $match = 'mapped';
                    $pdo->prepare("UPDATE products SET category_path = ?, category_match = ? WHERE id = ?")
                        ->execute([$local, $match, $pid]);
                } else {
                    $pdo->prepare("UPDATE products SET category_match = ? WHERE id = ?")->execute([$match, $pid]);
                }
            }
            
            // Varyant
            $sku = $it['sku'] ?? null;
            $price = $it['price'] ?? ($it['regular_price'] ?? null);
            $stock = $it['stock_quantity'] ?? ($it['stock'] ?? null);
            $attrs = [];
            if (!empty($it['attributes'])) {
                foreach ($it['attributes'] as $a) {
                    if (isset($a['name'], $a['value'])) {
                        $attrs[$a['name']] = $a['value'];
                    }
                }
            }
            $vid = $this->upsertVariant($pid, $sku, $price, $stock, $attrs);
            
            // Attribute eşleşmesi kontrol
            $vmatch = 'unmapped';
            if ($attrs) {
                $st = $pdo->prepare("SELECT external_key FROM attribute_mapping WHERE tenant_id = ? AND marketplace_id = 2");
                $st->execute([$tenant]);
                $keys = $st->fetchAll(\PDO::FETCH_COLUMN);
                if ($keys) {
                    $vmatch = 'mapped';
                }
            }
            $pdo->prepare("UPDATE variants SET attrs_match = ? WHERE id = ?")->execute([$vmatch, $vid]);
            
            $n++;
        }
        return $n;
    }
    
    /**
     * Ürünü bul veya oluştur
     */
    private function findOrCreateProduct(int $tenant, string $name, string $brand, string $desc, ?string $sku): int {
        $pdo = Database::pdo();
        
        // Ürün var mı kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM products 
            WHERE tenant_id = ? AND name = ? AND brand = ?
            LIMIT 1
        ");
        $stmt->execute([$tenant, $name, $brand]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Yeni ürün oluştur
        $pdo->prepare("
            INSERT INTO products (
                tenant_id, name, brand, description, seller_sku, source_marketplace, 
                status, customer_id, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, 'internal', 'active', 1, NOW(), NOW())
        ")->execute([$tenant, $name, $brand, $desc, $sku]);
        
        return (int)$pdo->lastInsertId();
    }
    
    /**
     * Varyantı upsert et
     */
    private function upsertVariant(int $productId, ?string $sku, $price, $stock, array $attrs): int {
        $pdo = Database::pdo();
        
        // Tenant'ı ürün üzerinden al
        $tst = $pdo->prepare("SELECT tenant_id, name FROM products WHERE id = ?");
        $tst->execute([$productId]);
        $row = $tst->fetch();
        $tenant = (int)$row['tenant_id'];
        $pname = $row['name'] ?? 'PRD';
        
        // SKU normalize
        $sku = $sku !== null ? trim((string)$sku) : null;
        if ($sku === '') $sku = null;
        
        // SKU boş veya dolu ama çakışıyorsa → Auto üret
        if (!$sku || !\App\Utils\Sku::isFree($tenant, $sku)) {
            $sku = \App\Utils\Sku::ensure(substr($pname, 0, 10), $tenant, $sku);
        }
        
        // Var mı?
        $vv = $pdo->prepare("SELECT id FROM variants WHERE product_id = ? AND sku = ?");
        $vv->execute([$productId, $sku]);
        $vid = (int)($vv->fetchColumn() ?: 0);
        
        if (!$vid) {
            $pdo->prepare("
                INSERT INTO variants (product_id, sku, price, stock, attrs_json, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ")->execute([
                $productId, $sku, 
                $price !== null ? (float)$price : null, 
                $stock !== null ? (int)$stock : null, 
                json_encode($attrs, JSON_UNESCAPED_UNICODE)
            ]);
            return (int)$pdo->lastInsertId();
        } else {
            $pdo->prepare("
                UPDATE variants SET price = ?, stock = ?, attrs_json = ?, updated_at = NOW() 
                WHERE id = ?
            ")->execute([
                $price !== null ? (float)$price : null, 
                $stock !== null ? (int)$stock : null, 
                json_encode($attrs, JSON_UNESCAPED_UNICODE), 
                $vid
            ]);
            return $vid;
        }
    }
    
    /**
     * Ürünü upsert et (create veya update)
     */
    private function upsertProduct(array $product, int $tenantId, string $source): array {
        $pdo = Database::pdo();
        
        // Ürün var mı kontrol et
        $stmt = $pdo->prepare("
            SELECT id FROM products 
            WHERE tenant_id = ? AND external_id = ? AND source_marketplace = ?
            LIMIT 1
        ");
        $stmt->execute([$tenantId, $product['id'], $source]);
        $existing = $stmt->fetch();
        
        // SKU normalize ve Auto-SKU üret
        $sku = $product['sku'] ?? null;
        if ($sku !== null) {
            $sku = trim($sku);
            if ($sku === '') $sku = null;
        }
        
        if (!$sku || !\App\Utils\Sku::isFree($tenantId, $sku)) {
            $sku = \App\Utils\Sku::ensure(substr($product['name'] ?? $product['title'] ?? 'PRD', 0, 10), $tenantId, $sku);
        }
        
        if ($existing) {
            // Update
            $pdo->prepare("
                UPDATE products SET
                    name = ?, description = ?, price = ?, stock = ?, seller_sku = ?,
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([
                $product['name'] ?? $product['title'],
                $product['description'] ?? '',
                $this->parsePrice($product['price'] ?? $product['regular_price'] ?? 0),
                $product['stock_quantity'] ?? $product['stock'] ?? 0,
                $sku,
                $existing['id']
            ]);
            
            return ['action' => 'updated', 'id' => $existing['id']];
        } else {
            // Create
            $pdo->prepare("
                INSERT INTO products (
                    tenant_id, external_id, name, description, price, stock, seller_sku,
                    source_marketplace, status, customer_id, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, NOW(), NOW())
            ")->execute([
                $tenantId,
                $product['id'],
                $product['name'] ?? $product['title'],
                $product['description'] ?? '',
                $this->parsePrice($product['price'] ?? $product['regular_price'] ?? 0),
                $product['stock_quantity'] ?? $product['stock'] ?? 0,
                $sku,
                $source
            ]);
            
            $productId = $pdo->lastInsertId();
            
            // Marketplace mapping ekle
            $pdo->prepare("
                INSERT INTO product_marketplace_mapping (
                    product_id, marketplace_id, external_id, sync_status
                ) VALUES (?, ?, ?, 'synced')
            ")->execute([
                $productId,
                $source === 'trendyol' ? 1 : 2,
                $product['id']
            ]);
            
            return ['action' => 'created', 'id' => $productId];
        }
    }
    
    /**
     * Price değerini parse et
     */
    private function parsePrice($price): float {
        if (empty($price) || $price === '') {
            return 0.0;
        }
        return (float) $price;
    }
    
    /**
     * CSV'den ürün verilerini upsert et
     */
    private function upsertProductFromCsv(array $data, int $tenantId): void {
        $pdo = Database::pdo();
        
        // Ürün var mı kontrol et (SKU ile)
        $stmt = $pdo->prepare("
            SELECT id FROM products 
            WHERE tenant_id = ? AND seller_sku = ?
            LIMIT 1
        ");
        $stmt->execute([$tenantId, $data['sku'] ?? '']);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update
            $pdo->prepare("
                UPDATE products SET
                    name = ?, description = ?, price = ?, stock = ?,
                    brand = ?, category_path = ?, updated_at = NOW()
                WHERE id = ?
            ")->execute([
                $data['name'] ?? '',
                $data['description'] ?? '',
                $data['price'] ?? 0,
                $data['stock'] ?? 0,
                $data['brand'] ?? '',
                $data['category'] ?? '',
                $existing['id']
            ]);
        } else {
            // Create
            $pdo->prepare("
                INSERT INTO products (
                    tenant_id, seller_sku, name, description, price, stock,
                    brand, category_path, source_marketplace, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'internal', 'active', NOW(), NOW())
            ")->execute([
                $tenantId,
                $data['sku'] ?? '',
                $data['name'] ?? '',
                $data['description'] ?? '',
                $data['price'] ?? 0,
                $data['stock'] ?? 0,
                $data['brand'] ?? '',
                $data['category'] ?? ''
            ]);
        }
    }
}
