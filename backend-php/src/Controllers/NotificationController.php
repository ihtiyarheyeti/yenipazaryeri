<?php
namespace App\Controllers;
use App\Database;

final class NotificationController {
  public function list(array $p,array $b,array $q): array {
    $tenant=(int)(\App\Context::$tenantId);
    $st=Database::pdo()->prepare("SELECT id,title,body,url,created_at FROM notifications WHERE tenant_id=? ORDER BY id DESC LIMIT 100");
    $st->execute([$tenant]); 
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }
}
