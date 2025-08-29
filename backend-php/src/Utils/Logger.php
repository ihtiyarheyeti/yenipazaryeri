<?php
namespace App\Utils;
use App\Database;

final class Logger {
  public static function log(?int $tenantId, ?int $productId, string $type, string $status, string $msg): void {
    $st = Database::pdo()->prepare("INSERT INTO logs (tenant_id, product_id, type, status, message, created_at) VALUES (?,?,?,?,?,NOW())");
    $st->execute([$tenantId,$productId,$type,$status,substr($msg,0,500)]);
  }
}
