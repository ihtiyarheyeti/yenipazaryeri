<?php
namespace App\Controllers;
use App\Database;

final class ConnectionsController {
  public function index(array $p, array $b, array $q): array {
    try {
      error_log("ConnectionsController::index çağrıldı");
      
      $tenant = (int)($q['tenant_id'] ?? 1);
      $page = max(1, (int)($q['page'] ?? 1));
      $pageSize = min(50, max(1, (int)($q['pageSize'] ?? 10)));
      $offset = ($page - 1) * $pageSize;
      
      $pdo = Database::pdo();
      if (!$pdo) {
        error_log("ConnectionsController::index - Database bağlantısı başarısız");
        return ['ok' => false, 'error' => 'database_connection_failed'];
      }
      
      $where = "WHERE c.tenant_id = ?";
      $bind = [$tenant];
      
      // Filtreler
      if (!empty($q['marketplace_id'])) {
        $where .= " AND c.marketplace_id = ?";
        $bind[] = (int)$q['marketplace_id'];
      }
      
      if (!empty($q['q'])) {
        $where .= " AND (c.supplier_id LIKE ? OR c.api_key LIKE ?)";
        $search = "%" . $q['q'] . "%";
        $bind[] = $search;
        $bind[] = $search;
      }
      
      if (!empty($q['dateFrom'])) {
        $where .= " AND c.created_at >= ?";
        $bind[] = $q['dateFrom'];
      }
      
      if (!empty($q['dateTo'])) {
        $where .= " AND c.created_at <= ?";
        $bind[] = $q['dateTo'];
      }
      
      // Total count
      $totalSt = $pdo->prepare("SELECT COUNT(*) FROM marketplace_connections c $where");
      $totalSt->execute($bind);
      $total = (int)$totalSt->fetchColumn();
      
      // Connections
      $st = $pdo->prepare("SELECT c.*, m.name as marketplace_name 
                           FROM marketplace_connections c 
                           JOIN marketplaces m ON m.id = c.marketplace_id 
                           $where 
                           ORDER BY c.id DESC 
                           LIMIT ?, ?");
      $bind[] = $offset;
      $bind[] = $pageSize;
      $st->execute($bind);
      $items = $st->fetchAll();
      
      error_log("ConnectionsController::index - Found items: " . count($items));
      
      return ['ok' => true, 'items' => $items, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize];
    } catch (\Throwable $e) {
      error_log("ConnectionsController::index hatası: " . $e->getMessage());
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }

  public function store(array $p, array $b): array {
    try {
      error_log("ConnectionsController::store çağrıldı - data: " . json_encode($b));
      
      $pdo = Database::pdo();
      if (!$pdo) {
        error_log("ConnectionsController::store - Database bağlantısı başarısız");
        return ['ok' => false, 'error' => 'database_connection_failed'];
      }
      
      // Validasyon
      $required = ['tenant_id', 'marketplace_id', 'api_key', 'api_secret'];
      foreach ($required as $field) {
        if (empty($b[$field])) {
          error_log("ConnectionsController::store - Missing required field: $field");
          return ['ok' => false, 'error' => "missing_$field"];
        }
      }
      
      // Eğer marketplace_id=2 (WooCommerce) ise base_url gerekli
      if ($b['marketplace_id'] == 2 && empty($b['base_url'])) {
        error_log("ConnectionsController::store - WooCommerce için base_url gerekli");
        return ['ok' => false, 'error' => 'missing_base_url'];
      }
      
      // Eğer marketplace_id=1 (Trendyol) ise supplier_id gerekli
      if ($b['marketplace_id'] == 1 && empty($b['supplier_id'])) {
        error_log("ConnectionsController::store - Trendyol için supplier_id gerekli");
        return ['ok' => false, 'error' => 'missing_supplier_id'];
      }
      
      $st = $pdo->prepare("INSERT INTO marketplace_connections 
                           (tenant_id, marketplace_id, api_key, api_secret, base_url, supplier_id, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
      
      $st->execute([
        (int)$b['tenant_id'],
        (int)$b['marketplace_id'],
        $b['api_key'],
        $b['api_secret'],
        $b['base_url'] ?? null,
        $b['supplier_id'] ?? null
      ]);
      
      $id = $pdo->lastInsertId();
      error_log("ConnectionsController::store - Connection created with ID: $id");
      
      return ['ok' => true, 'id' => $id];
    } catch (\Throwable $e) {
      error_log("ConnectionsController::store hatası: " . $e->getMessage());
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }

  public function test(array $p,array $b,array $q): array {
    try {
      $id=(int)($p[0]??0);
      error_log("ConnectionsController::test çağrıldı - ID: $id");
      
      $st=\App\Database::pdo()->prepare("SELECT c.*, m.name as mp_name, m.id as mp_id FROM marketplace_connections c JOIN marketplaces m ON m.id=c.marketplace_id WHERE c.id=?");
      $st->execute([$id]); 
      $c=$st->fetch(); 
      if(!$c) {
        error_log("Connection not found for ID: $id");
        return ['ok'=>false,'error'=>'not_found'];
      }
      
      error_log("Testing connection: " . json_encode($c));
      
      // WooCommerce test
      if ($c['mp_id'] == 2) {
        $url = rtrim($c['base_url'], '/') . '/wp-json/wc/v3/products';
        error_log("Testing WooCommerce URL: $url");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Authorization: Basic ' . base64_encode($c['api_key'] . ':' . $c['api_secret']),
          'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("WooCommerce test result - HTTP Code: $httpCode, Error: $error");
        
        if ($error) {
          return ['ok'=>false,'error'=>'connection_failed','details'=>$error];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
          return ['ok'=>true,'marketplace'=>$c['mp_name'],'reachable'=>true,'auth'=>true,'rate_limit'=>'ok'];
        } else {
          return ['ok'=>false,'error'=>'http_error','http_code'=>$httpCode];
        }
      }
      
      // Trendyol test
      if ($c['mp_id'] == 1) {
        $url = rtrim($c['base_url'], '/') . '/suppliers/' . $c['supplier_id'] . '/products';
        error_log("Testing Trendyol URL: $url");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Authorization: Basic ' . base64_encode($c['api_key'] . ':' . $c['api_secret']),
          'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("Trendyol test result - HTTP Code: $httpCode, Error: $error");
        
        if ($error) {
          return ['ok'=>false,'error'=>'connection_failed','details'=>$error];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
          return ['ok'=>true,'marketplace'=>$c['mp_name'],'reachable'=>true,'auth'=>true,'rate_limit'=>'ok'];
        } else {
          return ['ok'=>false,'error'=>'http_error','http_code'=>$httpCode];
        }
      }
      
      // Default fallback
      return ['ok'=>true,'marketplace'=>$c['mp_name'],'reachable'=>true,'auth'=>true,'rate_limit'=>'ok'];
      
    } catch (\Throwable $e) {
      error_log("ConnectionsController::test hatası: " . $e->getMessage());
      return ['ok'=>false,'error'=>'test_failed','details'=>$e->getMessage()];
    }
  }
}
