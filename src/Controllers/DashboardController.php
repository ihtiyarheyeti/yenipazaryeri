<?php
namespace App\Controllers;

use App\Database;

final class DashboardController {

  /** Dashboard metrikleri */
  public function metrics(array $params = [], array $body = [], array $query = []): array {
    $tenant = (int)($query['tenant_id'] ?? 1);
    $pdo = Database::pdo();

    try {
      $totProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE tenant_id={$tenant}")->fetchColumn();
      $totVariants = (int)$pdo->query("SELECT COUNT(*) FROM variants v JOIN products p ON p.id=v.product_id WHERE p.tenant_id={$tenant}")->fetchColumn();
      $mappedTY    = (int)$pdo->query("SELECT COUNT(*) FROM product_marketplace_mapping m JOIN products p ON p.id=m.product_id WHERE p.tenant_id={$tenant} AND m.marketplace_id=1")->fetchColumn();
      $mappedWOO   = (int)$pdo->query("SELECT COUNT(*) FROM product_marketplace_mapping m JOIN products p ON p.id=m.product_id WHERE p.tenant_id={$tenant} AND m.marketplace_id=2")->fetchColumn();

      $jobsPending = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='pending'")->fetchColumn();
      $jobsError   = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='error'")->fetchColumn();
      $jobsDead    = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='dead'")->fetchColumn();

      // Son 10 log
      $st = $pdo->prepare("SELECT id,level,status,message,created_at FROM logs ORDER BY id DESC LIMIT 10");
      $st->execute(); 
      $recentLogs = $st->fetchAll(\PDO::FETCH_ASSOC);

      return [
        'ok' => true,
        'data' => [
          'totProducts'  => $totProducts,
          'totVariants'  => $totVariants,
          'mappedTY'     => $mappedTY,
          'mappedWOO'    => $mappedWOO,
          'jobsPending'  => $jobsPending,
          'jobsError'    => $jobsError,
          'jobsDead'     => $jobsDead,
          'recentLogs'   => $recentLogs
        ]
      ];
    } catch (\Exception $e) {
      return ['ok' => false, 'error' => 'Dashboard metrics error: ' . $e->getMessage()];
    }
  }

  /** Dashboard hata logları */
  public function alerts(array $params = [], array $body = [], array $query = []): array {
    try {
      $pdo = Database::pdo();
      $st  = $pdo->query("SELECT id,level,status,message,created_at FROM logs WHERE level='error' ORDER BY id DESC LIMIT 20");
      $items = $st->fetchAll(\PDO::FETCH_ASSOC);

      return ['ok' => true, 'items' => $items];
    } catch (\Exception $e) {
      return ['ok' => false, 'error' => 'Dashboard alerts error: ' . $e->getMessage()];
    }
  }
}
