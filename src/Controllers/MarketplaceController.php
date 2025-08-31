<?php
namespace App\Controllers;

use App\Database;

final class MarketplaceController {
  /** Marketplaces listesi */
  public function index(array $params = [], array $body = [], array $query = []): array {
    try {
      $pdo = Database::pdo();
      if (!$pdo) {
        return ['ok' => false, 'error' => 'database_connection_failed'];
      }

      $st = $pdo->query("SELECT id, name, base_url FROM marketplaces ORDER BY id ASC");
      $items = $st ? $st->fetchAll(\PDO::FETCH_ASSOC) : [];

      return ['ok' => true, 'items' => $items];
    } catch (\Throwable $e) {
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }

  /** Yeni marketplace kaydet */
  public function store(array $params = [], array $body = [], array $query = []): array {
    try {
      $pdo = Database::pdo();
      $st = $pdo->prepare(
        "INSERT INTO marketplaces (name, base_url, created_at, updated_at) VALUES (?, ?, NOW(), NOW())"
      );
      $st->execute([
        $body['name'] ?? '',
        $body['base_url'] ?? null
      ]);

      return ['ok' => true, 'id' => $pdo->lastInsertId()];
    } catch (\Throwable $e) {
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }
}
