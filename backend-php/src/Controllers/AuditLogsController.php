<?php
namespace App\Controllers;
use App\Database;
use App\Context;

final class AuditLogsController {
  public function index(array $p, array $b, array $q): array {
    $tenant = (int)($q['tenant_id'] ?? Context::$tenantId);
    $st = Database::pdo()->prepare("SELECT a.id,a.user_id,u.email,u.name,a.action,a.resource,a.created_at
                                  FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id
                                  WHERE a.tenant_id=? ORDER BY a.id DESC LIMIT 300");
    $st->execute([$tenant]);
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }
}
