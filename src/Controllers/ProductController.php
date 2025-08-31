<?php
namespace App\Controllers;

use App\Database;

class ProductController {
  
  public function index(array $p, array $b, array $q): array {
    try {
      $tenantId = \App\Context::$tenantId;
      
      $pdo = \App\Database::pdo();
      if (!$pdo) {
        return ['ok' => false, 'error' => 'database_connection_failed'];
      }
      
      // Sadece aktif tenant'ın ürünlerini say
      $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE tenant_id = ?");
      $countStmt->execute([$tenantId]);
      $count = (int)$countStmt->fetchColumn();
      
      // Sadece aktif tenant'ın ürünlerini getir
      $stmt = $pdo->prepare("SELECT id, name, thumbnail_url, created_at, updated_at 
                             FROM products 
                             WHERE tenant_id = ? 
                             ORDER BY id DESC");
      $stmt->execute([$tenantId]);
      $items = $stmt->fetchAll();
      
      return [
        'ok' => true,
        'count' => $count,
        'items' => $items
      ];
    } catch (\Throwable $e) {
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
