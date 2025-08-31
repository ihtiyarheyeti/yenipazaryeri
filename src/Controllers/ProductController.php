<?php
namespace App\Controllers;

use App\Database;

class ProductController {
  
  public function index(array $p, array $b, array $q): array {
    try {
      error_log("ProductController::index çağrıldı - tenant: " . ($q['tenant_id'] ?? 'null'));
      
      $tenant = (int)($q['tenant_id'] ?? \App\Context::$tenantId); 
      $page = max(1, (int)($q['page'] ?? 1)); 
      $ps = min(50, max(1, (int)($q['pageSize'] ?? 10)));
      $off = ($page - 1) * $ps; 
      
      error_log("ProductController::index - tenant: $tenant, page: $page, pageSize: $ps, offset: $off");
      
      $pdo = \App\Database::pdo();
      if (!$pdo) {
        error_log("ProductController::index - Database bağlantısı başarısız");
        return ['ok' => false, 'error' => 'database_connection_failed'];
      }
      
      $where = "WHERE p.tenant_id=?"; 
      $bind = [$tenant];
      
      // Gelişmiş arama ve filtreler
      if($qstr = trim((string)($q['q'] ?? ''))) { 
        $where .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)"; 
        array_push($bind, "%$qstr%", "%$qstr%", "%$qstr%"); 
      }
      
      if($brand = trim((string)($q['brand'] ?? ''))) { 
        $where .= " AND p.brand=?"; 
        $bind[] = $brand; 
      }
      
      if(isset($q['priceMin'])) { 
        $where .= " AND EXISTS(SELECT 1 FROM variants v WHERE v.product_id=p.id AND v.price>=?)"; 
        $bind[] = (float)$q['priceMin']; 
      }
      
      if(isset($q['priceMax'])) { 
        $where .= " AND EXISTS(SELECT 1 FROM variants v WHERE v.product_id=p.id AND v.price<=?)"; 
        $bind[] = (float)$q['priceMax']; 
      }
      
      if(isset($q['stockMin'])) { 
        $where .= " AND EXISTS(SELECT 1 FROM variants v WHERE v.product_id=p.id AND v.stock>=?)"; 
        $bind[] = (int)$q['stockMin']; 
      }
      
      if(isset($q['stockMax'])) { 
        $where .= " AND EXISTS(SELECT 1 FROM variants v WHERE v.product_id=p.id AND v.stock<=?)"; 
        $bind[] = (int)$q['stockMax']; 
      }
      
      if($mapped = $q['mapped'] ?? null) {
        if($mapped === 'none') { 
          $where .= " AND NOT EXISTS(SELECT 1 FROM product_marketplace_mapping m WHERE m.product_id=p.id)"; 
        }
        if($mapped === 'ty') { 
          $where .= " AND EXISTS(SELECT 1 FROM product_marketplace_mapping m WHERE m.product_id=p.id AND m.marketplace_id=1)"; 
        }
        if($mapped === 'woo') { 
          $where .= " AND EXISTS(SELECT 1 FROM product_marketplace_mapping m WHERE m.product_id=p.id AND m.marketplace_id=2)"; 
        }
      }
      
      if(($mp = (int)($q['mp'] ?? 0)) > 0) { 
        $where .= " AND EXISTS(SELECT 1 FROM product_marketplace_mapping m WHERE m.product_id=p.id AND m.marketplace_id=?)"; 
        $bind[] = $mp; 
      }
      
      // Source filtresi (origin_mp)
      if($source = trim((string)($q['source'] ?? ''))) {
        if(in_array($source, ['woo', 'trendyol', 'local'])) {
          $where .= " AND p.origin_mp = ?";
          $bind[] = $source;
        }
      }
      
      // Only unmapped filtresi
      if (!empty($q['only_unmapped'])) {
        $where .= " AND (p.category_match='unmapped' OR EXISTS(SELECT 1 FROM variants v2 WHERE v2.product_id=p.id AND v2.attrs_match='unmapped'))";
      }
      
      // Search filtresi (name ve SKU)
      if ($search = trim((string)($q['search'] ?? ''))) {
        $where .= " AND (p.name LIKE ? OR EXISTS(SELECT 1 FROM variants v3 WHERE v3.product_id=p.id AND (v3.sku LIKE ?)))";
        $bind[] = "%$search%";
        $bind[] = "%$search%";
      }
      
      if(!empty($q['dateFrom'])) { 
        $where .= " AND p.created_at>=?"; 
        $bind[] = $q['dateFrom']; 
      }
      
      if(!empty($q['dateTo'])) { 
        $where .= " AND p.created_at<=?"; 
        $bind[] = $q['dateTo']; 
      }
      
      // Status filtresi
      if(!empty($q['status']) && in_array($q['status'],['draft','active','archived'],true)){
        $where.=" AND p.status=?"; $bind[]=$q['status'];
      }
      
      error_log("ProductController::index - SQL WHERE: $where");
      error_log("ProductController::index - Bind params: " . json_encode($bind));
      
      $total = $pdo->prepare("SELECT COUNT(*) FROM products p $where"); 
      $total->execute($bind); 
      $tot = (int)$total->fetchColumn();
      
      error_log("ProductController::index - Total products: $tot");
      
      $st = $pdo->prepare("SELECT p.*, 
        p.origin_mp,
        p.origin_external_id,
        (SELECT COUNT(*) FROM variants v WHERE v.product_id=p.id) AS variant_count,
        (SELECT external_id FROM product_marketplace_mapping m WHERE m.product_id=p.id AND m.marketplace_id=1 LIMIT 1) AS trendyol_external_id,
        (SELECT external_id FROM product_marketplace_mapping m WHERE m.product_id=p.id AND m.marketplace_id=2 LIMIT 1) AS woo_external_id,
        p.category_match,
        (SELECT GROUP_CONCAT(v.attrs_match) FROM variants v WHERE v.product_id=p.id) AS attrs_match_status,
        (SELECT MIN(v.sku) FROM variants v WHERE v.product_id=p.id) AS first_sku
        FROM products p $where ORDER BY p.id DESC LIMIT $off,$ps");
      $st->execute($bind);
      
      $items = $st->fetchAll();
      error_log("ProductController::index - Found items: " . count($items));
      
      return ['ok' => true, 'items' => $items, 'total' => $tot, 'page' => $page, 'pageSize' => $ps];
    } catch (\Throwable $e) {
      error_log("ProductController::index hatası: " . $e->getMessage());
      error_log("ProductController::index stack trace: " . $e->getTraceAsString());
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }

  public function create(array $p, array $b): array {
    // Validasyon
    $errs = \App\Utils\Validator::require($b, ['tenant_id', 'name']);
    if($errs) { 
      http_response_code(422); 
      return ['ok'=>false, 'error'=>'validation', 'fields'=>$errs]; 
    }
    
    if(!\App\Utils\Validator::minlen($b['name']??'', 2)) { 
      http_response_code(422); 
      return ['ok'=>false, 'error'=>'validation', 'fields'=>['name'=>'min:2']]; 
    }
    
    if(!\App\Utils\Validator::positive($b['tenant_id'])) {
      http_response_code(422);
      return ['ok'=>false, 'error'=>'validation', 'fields'=>['tenant_id'=>'must_be_positive']];
    }
    
    $pdo = \App\Database::pdo();
    $st = $pdo->prepare("INSERT INTO products (tenant_id,name,brand,description,category_path,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())");
    $cat = json_encode($b['category_path'] ?? []);
    $st->execute([(int)$b['tenant_id'], $b['name'] ?? '', $b['brand'] ?? null, $b['description'] ?? null, $cat]);
    return ['ok' => true, 'id' => $pdo->lastInsertId()];
  }

  public function show(array $p, array $b): array {
    $id = (int)($p[0] ?? 0);
    if($id <= 0) return ['ok' => false, 'error' => 'Invalid ID'];
    
    $pdo = \App\Database::pdo();
    $st = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $st->execute([$id]);
    $product = $st->fetch();
    
    if(!$product) return ['ok' => false, 'error' => 'Product not found'];
    
    return ['ok' => true, 'product' => $product];
  }

  public function store(array $p, array $b): array {
    return $this->create($p, $b);
  }

  public function update(array $p, array $b): array {
    $id = (int)($p[0] ?? 0);
    if($id <= 0) return ['ok' => false, 'error' => 'Invalid ID'];
    
    // Arşivli ürün yazılamaz kontrolü
    $st = \App\Database::pdo()->prepare("SELECT status FROM products WHERE id=?");
    $st->execute([$id]); 
    $stt = $st->fetchColumn();
    if($stt === 'archived'){ 
      http_response_code(409); 
      return ['ok'=>false,'error'=>'archived_readonly']; 
    }
    
    $pdo = \App\Database::pdo();
    $st = $pdo->prepare("UPDATE products SET name=?, brand=?, description=?, category_path=?, updated_at=NOW() WHERE id=?");
    $cat = json_encode($b['category_path'] ?? []);
    $st->execute([$b['name'] ?? '', $b['brand'] ?? null, $b['description'] ?? null, $cat, $id]);
    
    return ['ok' => true];
  }

  public function destroy(array $p, array $b): array {
    $id = (int)($p[0] ?? 0);
    if($id <= 0) return ['ok' => false, 'error' => 'Invalid ID'];
    
    $pdo = \App\Database::pdo();
    $st = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $st->execute([$id]);
    
    return ['ok' => true];
  }

  // Yeni lifecycle metodları
  public function publish(array $p): array {
    $id = (int)($p[0] ?? 0);
    $pdo = \App\Database::pdo();
    $pdo->prepare("UPDATE products SET status='active', published_at=NOW(), archived_at=NULL, updated_at=NOW() WHERE id=?")->execute([$id]);
    \App\Middleware\Audit::log($this->userId ?? null, 'product.publish', "/products/$id", null);
    return ['ok' => true];
  }

  public function archive(array $p): array {
    $id = (int)($p[0] ?? 0);
    $pdo = \App\Database::pdo();
    $pdo->prepare("UPDATE products SET status='archived', archived_at=NOW(), updated_at=NOW() WHERE id=?")->execute([$id]);
    \App\Middleware\Audit::log($this->userId ?? null, 'product.archive', "/products/$id", null);
    return ['ok' => true];
  }

  public function restore(array $p): array {
    $id = (int)($p[0] ?? 0);
    $pdo = \App\Database::pdo();
    $pdo->prepare("UPDATE products SET status='draft', archived_at=NULL, updated_at=NOW() WHERE id=?")->execute([$id]);
    \App\Middleware\Audit::log($this->userId ?? null, 'product.restore', "/products/$id", null);
    return ['ok' => true];
  }

  // Toplu durum değişikliği
  public function bulkStatus(array $p, array $b): array {
    $ids = $b['ids'] ?? []; 
    $status = $b['status'] ?? 'draft';
    if(!in_array($status, ['draft', 'active', 'archived'], true)) return ['ok' => false, 'error' => 'bad_status'];
    if(!$ids || !is_array($ids)) return ['ok' => false, 'error' => 'empty_ids'];
    
    $pdo = \App\Database::pdo();
    $sql = "UPDATE products SET status=?, updated_at=NOW(), 
            published_at=IF(?='active', NOW(), published_at),
            archived_at=IF(?='archived', NOW(), IF(?<>'archived', NULL, archived_at))
            WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")";
    $pdo->prepare($sql)->execute([$status, $status, $status, $status]);
    
    \App\Middleware\Audit::log($this->userId ?? null, 'product.bulk_status', '/products/bulk-status', ['status' => $status, 'ids' => $ids]);
    return ['ok' => true, 'count' => count($ids)];
  }

  // Ürün onay/red
  public function review(array $p, array $b): array {
    $id = (int)($p[0] ?? 0);
    $status = $b['status'] ?? 'pending';
    if(!in_array($status, ['pending', 'approved', 'rejected'], true)) return ['ok' => false, 'error' => 'bad_status'];
    $note = trim((string)($b['note'] ?? ''));
    $uid = $this->userId ?? 0;
    
    $pdo = \App\Database::pdo();
    $pdo->prepare("UPDATE products SET review_status=?, reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE id=?")
        ->execute([$status, $uid, $note, $id]);
    
    \App\Middleware\Audit::log($uid, 'product.review', "/products/$id", ['status' => $status, 'note' => $note]);
    return ['ok' => true];
  }
}
