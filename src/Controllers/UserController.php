<?php
namespace App\Controllers;
use App\Database;

final class UserController {
  // GET /users?tenant_id=1&page=1&pageSize=10&q=ali
  public function index(array $p, array $b, array $q): array {
    $pdo = Database::pdo();

    $page     = max(1, (int)($q['page'] ?? 1));
    $pageSize = min(50, max(1, (int)($q['pageSize'] ?? 10)));
    $offset   = ($page - 1) * $pageSize;
    $search   = trim((string)($q['q'] ?? ''));
    $tenantId = (int)($q['tenant_id'] ?? 0);

    $where = "WHERE 1=1";
    $bind  = [];
    if ($tenantId > 0) { $where .= " AND u.tenant_id = :t"; $bind[':t'] = $tenantId; }
    if ($search !== '') {
      $where .= " AND (u.name LIKE :s OR u.email LIKE :s)";
      $bind[':s'] = "%$search%";
    }

    $count = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
    $count->execute($bind);
    $total = (int)$count->fetchColumn();

    // Roller: GROUP_CONCAT ile tek satıra toplayıp diziye split edeceğiz
    $sql = "
      SELECT 
        u.id, u.tenant_id, u.name, u.email, u.role,
        COALESCE(GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ','),'') AS roles_csv
      FROM users u
      LEFT JOIN user_roles ur ON ur.user_id = u.id
      LEFT JOIN roles r ON r.id = ur.role_id
      $where
      GROUP BY u.id
      ORDER BY u.id DESC
      LIMIT $offset,$pageSize
    ";
    $st = $pdo->prepare($sql);
    $st->execute($bind);
    $items = [];
    while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
      $row['roles'] = $row['roles_csv'] ? explode(',', $row['roles_csv']) : [];
      unset($row['roles_csv']);
      $items[] = $row;
    }

    return ['ok'=>true,'items'=>$items,'total'=>$total,'page'=>$page,'pageSize'=>$pageSize];
  }

  public function invite(array $p, array $b): array {
    $email = trim($b['email'] ?? '');
    $tenant = (int)($b['tenant_id'] ?? 0);
    if ($email === '' || $tenant <= 0) return ['ok' => false, 'error' => 'email & tenant_id required'];
    
    $pdo = \App\Database::pdo();
    $pass = bin2hex(random_bytes(4));
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $st = $pdo->prepare("INSERT INTO users (tenant_id,email,password_hash,created_at,updated_at) VALUES (?,?,?,NOW(),NOW())");
    $st->execute([$tenant, $email, $hash]);
    
    \App\Utils\Mailer::send($email, "Davet", "Geçici şifreniz: $pass");
    return ['ok' => true, 'email' => $email];
  }
}
