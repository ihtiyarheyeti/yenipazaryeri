<?php
namespace App\Controllers;

use App\Database;

class VariantController {
  
  public function index(array $p, array $b, array $q): array {
    $page = max(1, (int)($q['page'] ?? 1)); 
    $ps = min(100, max(1, (int)($q['pageSize'] ?? 20))); 
    $off = ($page - 1) * $ps;
    $productId = (int)($q['product_id'] ?? 0);
    
    $pdo = \App\Database::pdo();
    $where = "WHERE 1=1"; 
    $bind = [];
    
    if($productId > 0) { 
      $where .= " AND v.product_id=?"; 
      $bind[] = $productId; 
    }
    
    // Gelişmiş filtreler
    if($qstr = trim((string)($q['q'] ?? ''))) { 
      $where .= " AND v.sku LIKE ?"; 
      $bind[] = "%$qstr%"; 
    }
    
    if(isset($q['priceMin'])) { 
      $where .= " AND v.price>=?"; 
      $bind[] = (float)$q['priceMin']; 
    }
    
    if(isset($q['priceMax'])) { 
      $where .= " AND v.price<=?"; 
      $bind[] = (float)$q['priceMax']; 
    }
    
    if(isset($q['stockMin'])) { 
      $where .= " AND v.stock>=?"; 
      $bind[] = (int)$q['stockMin']; 
    }
    
    if(isset($q['stockMax'])) { 
      $where .= " AND v.stock<=?"; 
      $bind[] = (int)$q['stockMax']; 
    }
    
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM variants v $where"); 
    $cnt->execute($bind); 
    $tot = (int)$cnt->fetchColumn();
    
    $st = $pdo->prepare("SELECT v.*, p.name as product_name FROM variants v 
      LEFT JOIN products p ON p.id = v.product_id 
      $where ORDER BY v.id DESC LIMIT $off,$ps"); 
    $st->execute($bind);
    
    return ['ok' => true, 'items' => $st->fetchAll(), 'total' => $tot, 'page' => $page, 'pageSize' => $ps];
  }

  public function create(array $p, array $b): array {
    $pdo = \App\Database::pdo();
    $st = $pdo->prepare("INSERT INTO variants (product_id,sku,price,stock,attrs,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())");
    $st->execute([(int)$b['product_id'], $b['sku'] ?? null, (float)($b['price'] ?? 0), (int)($b['stock'] ?? 0), json_encode($b['attrs'] ?? (object)[])]);
    return ['ok' => true, 'id' => $pdo->lastInsertId()];
  }

  public function show(array $p, array $b): array {
    $id = (int)($p[0] ?? 0);
    if($id <= 0) return ['ok' => false, 'error' => 'Invalid ID'];
    
    $pdo = \App\Database::pdo();
    $st = $pdo->prepare("SELECT * FROM variants WHERE id = ?");
    $st->execute([$id]);
    $variant = $st->fetch();
    
    if(!$variant) return ['ok' => false, 'error' => 'Variant not found'];
    
    return ['ok' => true, 'variant' => $variant];
  }

  public function store(array $p, array $b): array {
    return $this->create($p, $b);
  }

  public function update(array $p, array $b): array {
    $id = (int)($p[0] ?? 0);
    if($id <= 0) return ['ok' => false, 'error' => 'Invalid ID'];
    
    $pdo = \App\Database::pdo();
    $st = $pdo->prepare("UPDATE variants SET sku=?, price=?, stock=?, attrs=?, updated_at=NOW() WHERE id=?");
    $st->execute([$b['sku'] ?? null, (float)($b['price'] ?? 0), (int)($b['stock'] ?? 0), json_encode($b['attrs'] ?? (object)[]), $id]);
    
    return ['ok' => true];
  }

  public function destroy(array $p, array $b): array {
    $id = (int)($p[0] ?? 0);
    if($id <= 0) return ['ok' => false, 'error' => 'Invalid ID'];
    
    $pdo = \App\Database::pdo();
    $st = $pdo->prepare("DELETE FROM variants WHERE id = ?");
    $st->execute([$id]);
    
    return ['ok' => true];
  }
}
