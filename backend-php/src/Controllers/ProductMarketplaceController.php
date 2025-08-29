<?php
namespace App\Controllers;
use App\Database;
use PDO;

final class ProductMarketplaceController {

  // Listeleme: ürün ya da pazaryerine göre
  public function index(array $p, array $b, array $q): array {
    $pdo = Database::pdo();
    $where = []; $bind = [];
    if (!empty($q['product_id'])) { $where[] = "pmm.product_id = :pid"; $bind[':pid']=(int)$q['product_id']; }
    if (!empty($q['marketplace_id'])) { $where[] = "pmm.marketplace_id = :mid"; $bind[':mid']=(int)$q['marketplace_id']; }

    $sql = "
      SELECT pmm.id, pmm.product_id, pmm.marketplace_id, pmm.external_product_id, pmm.last_sync,
             m.name AS marketplace_name, p.name AS product_name
      FROM product_marketplace_mapping pmm
      JOIN marketplaces m ON m.id = pmm.marketplace_id
      JOIN products p ON p.id = pmm.product_id
    ";
    if ($where) $sql .= " WHERE ".implode(" AND ", $where);
    $sql .= " ORDER BY pmm.id DESC LIMIT 500";

    $st = $pdo->prepare($sql);
    $st->execute($bind);
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }

  // Eşleştirme ekle/güncelle
  // body: { product_id, marketplace_id, external_product_id }
  public function attach(array $p, array $b): array {
    $pid = (int)($b['product_id'] ?? 0);
    $mid = (int)($b['marketplace_id'] ?? 0);
    $ext = trim((string)($b['external_product_id'] ?? ''));
    if ($pid<=0 || $mid<=0 || $ext==='') return ['ok'=>false,'error'=>'product_id, marketplace_id, external_product_id required'];

    $pdo = Database::pdo();

    // Var mı? → update; yoksa → insert
    $sel = $pdo->prepare("SELECT id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=?");
    $sel->execute([$pid, $mid]);
    $row = $sel->fetch();

    if ($row) {
      $upd = $pdo->prepare("UPDATE product_marketplace_mapping SET external_product_id=?, last_sync=CURRENT_TIMESTAMP WHERE id=?");
      $upd->execute([$ext, (int)$row['id']]);
      return ['ok'=>true,'id'=>(int)$row['id'],'updated'=>true];
    } else {
      $ins = $pdo->prepare("INSERT INTO product_marketplace_mapping (product_id, marketplace_id, external_product_id) VALUES (?,?,?)");
      $ins->execute([$pid, $mid, $ext]);
      return ['ok'=>true,'id'=>(int)$pdo->lastInsertId(),'created'=>true];
    }
  }

  // Eşleştirme sil
  // body: { product_id, marketplace_id }
  public function detach(array $p, array $b): array {
    $pid = (int)($b['product_id'] ?? 0);
    $mid = (int)($b['marketplace_id'] ?? 0);
    if ($pid<=0 || $mid<=0) return ['ok'=>false,'error'=>'product_id and marketplace_id required'];

    $st = Database::pdo()->prepare("DELETE FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=?");
    $st->execute([$pid, $mid]);
    return ['ok'=>true,'deleted'=>['product_id'=>$pid,'marketplace_id'=>$mid]];
  }
}
