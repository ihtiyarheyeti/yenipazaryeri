<?php
namespace App\Controllers;
use App\Database;

final class AuditController {
  public function index(array $p, array $b, array $q): array {
    $pdo = Database::pdo();
    $page = max(1, (int)($q['page'] ?? 1));
    $pageSize = min(100, max(1, (int)($q['pageSize'] ?? 20)));
    $offset = ($page - 1) * $pageSize;
    
    $st = $pdo->prepare("SELECT * FROM audit_logs ORDER BY id DESC LIMIT $offset,$pageSize");
    $st->execute();
    
    return ['ok' => true, 'items' => $st->fetchAll(), 'page' => $page, 'pageSize' => $pageSize];
  }
}

