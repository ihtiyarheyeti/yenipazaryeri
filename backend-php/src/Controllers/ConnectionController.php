<?php
namespace App\Controllers;

use App\Database;

final class ConnectionController {
  public function index(array $p, array $b, array $q): array {
    $tenant = (int)($q['tenant_id'] ?? \App\Context::$tenantId); 
    $page = max(1, (int)($q['page'] ?? 1));
    $ps = min(50, max(1, (int)($q['pageSize'] ?? 10))); 
    $off = ($page - 1) * $ps;
    
    $where = "WHERE c.tenant_id=?"; 
    $bind = [$tenant];
    
    // Gelişmiş filtreler
    if(($mp = (int)($q['marketplace_id'] ?? 0)) > 0) { 
      $where .= " AND c.marketplace_id=?"; 
      $bind[] = $mp; 
    }
    
    if($qstr = trim((string)($q['q'] ?? ''))) { 
      $where .= " AND (c.supplier_id LIKE ? OR c.api_key LIKE ?)"; 
      array_push($bind, "%$qstr%", "%$qstr%"); 
    }
    
    if(!empty($q['dateFrom'])) { 
      $where .= " AND c.created_at>=?"; 
      $bind[] = $q['dateFrom']; 
    }
    
    if(!empty($q['dateTo'])) { 
      $where .= " AND c.created_at<=?"; 
      $bind[] = $q['dateTo']; 
    }
    
    $cnt = Database::pdo()->prepare("SELECT COUNT(*) FROM marketplace_connections c $where");
    $cnt->execute($bind); 
    $total = (int)$cnt->fetchColumn();
    
    $sql = "SELECT c.*, m.name AS marketplace_name 
            FROM marketplace_connections c 
            JOIN marketplaces m ON m.id=c.marketplace_id
            $where ORDER BY c.id DESC LIMIT $off,$ps";
    $st = Database::pdo()->prepare($sql); 
    $st->execute($bind);
    
    return ['ok' => true, 'items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pageSize' => $ps];
  }

  public function create(array $p, array $b): array {
    $pdo = Database::pdo();
    $st = $pdo->prepare("INSERT INTO marketplace_connections (tenant_id,marketplace_id,api_key,api_secret,supplier_id,base_url,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())");
    $st->execute([(int)$b['tenant_id'], (int)$b['marketplace_id'], $b['api_key'] ?? null, $b['api_secret'] ?? null, $b['supplier_id'] ?? null, $b['base_url'] ?? null]);
    return ['ok' => true, 'id' => $pdo->lastInsertId()];
  }

  public function ping(array $p, array $b, array $q): array {
    $id = (int)($p[0] ?? 0);
    $st = Database::pdo()->prepare("SELECT c.*, m.base_url FROM marketplace_connections c JOIN marketplaces m ON m.id=c.marketplace_id WHERE c.id=?");
    $st->execute([$id]); 
    $row = $st->fetch();
    
    if(!$row) return ['ok' => false, 'error' => 'not_found'];

    // Minimal bir test: marketplace base_url'e HEAD isteği
    $url = $row['base_url'] ?: 'https://httpbin.org/get';
    $ch = curl_init($url); 
    curl_setopt_array($ch, [
      CURLOPT_NOBODY => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 8, 
      CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $ok = curl_exec($ch) !== false; 
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    curl_close($ch);
    
    return $ok && $code && $code < 500 ? ['ok' => true, 'http' => $code] : ['ok' => false, 'error' => 'ping_failed', 'http' => $code ?: 0];
  }

  public function delete(array $p): array {
    $id = (int)($p[0] ?? 0);
    if (!$id) return ['ok' => false, 'error' => 'invalid_id'];
    
    $pdo = Database::pdo();
    $st = $pdo->prepare("DELETE FROM marketplace_connections WHERE id = ?");
    $st->execute([$id]);
    
    return ['ok' => true, 'deleted' => $st->rowCount() > 0];
  }
}
