<?php
namespace App\Controllers;
use App\Database;

final class OptionValueController {
  public function index(array $p, array $b, array $q): array {
    $pdo = Database::pdo();

    $optionId = (int)($q['option_id'] ?? 0); // opsiyonel filtre
    $page = max(1, (int)($q['page'] ?? 1));
    $pageSize = min(50, max(1, (int)($q['pageSize'] ?? 10)));
    $offset = ($page - 1) * $pageSize;
    $search = trim((string)($q['q'] ?? ''));

    $where = "WHERE 1=1";
    $bind = [];
    if ($optionId > 0) { $where .= " AND option_id = :o"; $bind[':o'] = $optionId; }
    if ($search !== '') { $where .= " AND value LIKE :s"; $bind[':s'] = "%$search%"; }

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM option_values $where");
    $countSt->execute($bind);
    $total = (int)$countSt->fetchColumn();

    $st = $pdo->prepare("SELECT * FROM option_values $where ORDER BY id DESC LIMIT $offset,$pageSize");
    $st->execute($bind);
    $items = $st->fetchAll();

    return ['ok'=>true,'items'=>$items,'page'=>$page,'pageSize'=>$pageSize,'total'=>$total];
  }

  public function store(array $p, array $b): array {
    $option_id = (int)($b['option_id'] ?? 0);
    $value = trim($b['value'] ?? '');
    if ($option_id <= 0 || $value === '') return ['ok'=>false,'error'=>'option_id and value required'];
    $st = Database::pdo()->prepare("INSERT INTO option_values (option_id, value) VALUES (?,?)");
    $st->execute([$option_id, $value]);
    return ['ok'=>true,'id'=>(int)Database::pdo()->lastInsertId()];
  }

  public function update(array $p, array $b): array {
    $id = (int)$p[0];
    if (!isset($b['value'])) return ['ok'=>false,'error'=>'value required'];
    $st = Database::pdo()->prepare("UPDATE option_values SET value=? WHERE id=?");
    $st->execute([trim($b['value']), $id]);
    return ['ok'=>true,'id'=>$id];
  }

  public function destroy(array $p): array {
    $id = (int)$p[0];
    $st = Database::pdo()->prepare("DELETE FROM option_values WHERE id=?");
    $st->execute([$id]);
    return ['ok'=>true,'deleted'=>$id];
  }
}
