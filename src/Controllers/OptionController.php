<?php
namespace App\Controllers;
use App\Database;
use PDO;

final class OptionController {
  public function index(array $p, array $b, array $q): array {
    $pdo = Database::pdo();

    $tenantId = (int)($q['tenant_id'] ?? 0);
    if ($tenantId <= 0) return ['ok'=>false,'error'=>'tenant_id required'];

    $page = max(1, (int)($q['page'] ?? 1));
    $pageSize = min(50, max(1, (int)($q['pageSize'] ?? 10)));
    $offset = ($page - 1) * $pageSize;
    $search = trim((string)($q['q'] ?? ''));

    $where = "WHERE tenant_id = :t";
    $bind = [':t' => $tenantId];
    if ($search !== '') {
      $where .= " AND name LIKE :s";
      $bind[':s'] = "%$search%";
    }

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM options $where");
    $countSt->execute($bind);
    $total = (int)$countSt->fetchColumn();

    $st = $pdo->prepare("SELECT * FROM options $where ORDER BY id DESC LIMIT $offset,$pageSize");
    $st->execute($bind);
    $items = $st->fetchAll();

    return ['ok'=>true,'items'=>$items,'page'=>$page,'pageSize'=>$pageSize,'total'=>$total];
  }

  public function store(array $p, array $b): array {
    $tenant_id = (int)($b['tenant_id'] ?? 0);
    $name = trim($b['name'] ?? '');
    if ($tenant_id <= 0 || $name === '') return ['ok'=>false,'error'=>'tenant_id and name required'];
    $st = Database::pdo()->prepare("INSERT INTO options (tenant_id, name) VALUES (?,?)");
    $st->execute([$tenant_id, $name]);
    return ['ok'=>true,'id'=>(int)Database::pdo()->lastInsertId()];
  }

  public function update(array $p, array $b): array {
    $id = (int)$p[0];
    if (!isset($b['name'])) return ['ok'=>false,'error'=>'name required'];
    $st = Database::pdo()->prepare("UPDATE options SET name=? WHERE id=?");
    $st->execute([trim($b['name']), $id]);
    return ['ok'=>true,'id'=>$id];
  }

  public function destroy(array $p): array {
    $id = (int)$p[0];
    $st = Database::pdo()->prepare("DELETE FROM options WHERE id=?");
    $st->execute([$id]);
    return ['ok'=>true,'deleted'=>$id];
  }
}
