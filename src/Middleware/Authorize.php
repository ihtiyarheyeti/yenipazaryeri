<?php
namespace App\Middleware;
use App\Database;

final class Authorize {
  public static function can(int $userId, string $perm): bool {
    $pdo = Database::pdo();
    $sql = "SELECT 1
            FROM user_roles ur
            JOIN role_permissions rp ON rp.role_id=ur.role_id
            JOIN permissions p ON p.id=rp.permission_id
            WHERE ur.user_id=? AND p.name=? LIMIT 1";
    $st = $pdo->prepare($sql); 
    $st->execute([$userId, $perm]);
    return (bool)$st->fetchColumn();
  }
}
