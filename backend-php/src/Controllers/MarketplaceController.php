<?php
namespace App\Controllers;

use App\Database;

final class MarketplaceController {
  public function index(array $p, array $b, array $q): array {
    try {
      error_log("MarketplaceController::index çağrıldı");
      
      $pdo = Database::pdo();
      if (!$pdo) {
        error_log("Database bağlantısı başarısız");
        return ['ok' => false, 'error' => 'database_connection_failed'];
      }
      
      $st = $pdo->query("SELECT id,name,base_url FROM marketplaces ORDER BY id ASC");
      if (!$st) {
        error_log("Marketplaces query başarısız");
        return ['ok' => false, 'error' => 'query_failed'];
      }
      
      $items = $st->fetchAll();
      error_log("Marketplaces bulundu: " . count($items));
      error_log("Marketplaces data: " . json_encode($items));
      
      $response = ['ok' => true, 'items' => $items];
      error_log("MarketplaceController response: " . json_encode($response));
      
      return $response;
    } catch (\Throwable $e) {
      error_log("MarketplaceController::index hatası: " . $e->getMessage());
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }

  public function store(array $p, array $b): array {
    try {
      $pdo = Database::pdo();
      $st = $pdo->prepare("INSERT INTO marketplaces (name,base_url,created_at,updated_at) VALUES (?,?,NOW(),NOW())");
      $st->execute([$b['name'] ?? '', $b['base_url'] ?? null]);
      return ['ok' => true, 'id' => $pdo->lastInsertId()];
    } catch (\Throwable $e) {
      error_log("MarketplaceController::store hatası: " . $e->getMessage());
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }
}
