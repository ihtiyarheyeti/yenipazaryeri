<?php
namespace App\Controllers;
use App\Database;
use PDO;

final class ProductOptionValueController {
  public function index(array $p, array $b, array $q): array {
    $pdo = Database::pdo();
    if (empty($q['product_id'])) {
      return ['ok'=>false,'error'=>'product_id query param required'];
    }
    $st = $pdo->prepare("
      SELECT pov.product_id, pov.option_value_id, ov.value, o.name AS option_name
      FROM product_option_values pov
      JOIN option_values ov ON ov.id = pov.option_value_id
      JOIN options o ON o.id = ov.option_id
      WHERE pov.product_id = :pid
      ORDER BY o.name, ov.value
    ");
    $st->execute([':pid'=>(int)$q['product_id']]);
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }

  // attach: product'a option_value bağla
  public function attach(array $p, array $b): array {
    $pid = (int)($b['product_id'] ?? 0);
    $ov  = (int)($b['option_value_id'] ?? 0);
    if ($pid<=0 || $ov<=0) return ['ok'=>false,'error'=>'product_id and option_value_id required'];

    $st = Database::pdo()->prepare("INSERT IGNORE INTO product_option_values (product_id, option_value_id) VALUES (?,?)");
    $st->execute([$pid, $ov]);
    return ['ok'=>true,'product_id'=>$pid,'option_value_id'=>$ov];
  }

  // detach: ilişkisini kaldır
  public function detach(array $p, array $b): array {
    $pid = (int)($b['product_id'] ?? 0);
    $ov  = (int)($b['option_value_id'] ?? 0);
    if ($pid<=0 || $ov<=0) return ['ok'=>false,'error'=>'product_id and option_value_id required'];

    $st = Database::pdo()->prepare("DELETE FROM product_option_values WHERE product_id=? AND option_value_id=?");
    $st->execute([$pid, $ov]);
    return ['ok'=>true,'deleted'=>['product_id'=>$pid,'option_value_id'=>$ov]];
  }
}
