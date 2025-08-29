<?php
namespace App\Middleware;
use App\Database;

final class AuditMiddleware {
  public static function log(?int $userId, string $action, string $method, string $endpoint, $payload = null) {
    $pdo = Database::pdo();
    $st = $pdo->prepare("INSERT INTO audit_logs (user_id,action,method,endpoint,payload,ip) VALUES (?,?,?,?,?,?)");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $st->execute([$userId, $action, $method, $endpoint, $payload ? json_encode($payload) : null, $ip]);
  }
}

