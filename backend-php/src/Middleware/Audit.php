<?php
namespace App\Middleware;
use App\Database;
use App\Context;

final class Audit {
  public static function log(?int $userId, string $action, string $resource, $payload=null): void {
    try{
      $pdo = Database::pdo();
      $ua = $_SERVER['HTTP_USER_AGENT'] ?? ''; 
      $ip = $_SERVER['REMOTE_ADDR'] ?? '';
      $pdo->prepare("INSERT INTO audit_logs (tenant_id,user_id,action,resource,payload,ip,user_agent) VALUES (?,?,?,?,?,?,?)")
          ->execute([Context::$tenantId, $userId, $action, $resource, $payload ? json_encode($payload) : null, $ip, $ua]);
    }catch(\Throwable $e){}
  }
}
