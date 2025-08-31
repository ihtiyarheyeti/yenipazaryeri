<?php
namespace App\Utils;
use App\Database;

final class Reconcile {
  public static function write(int $tenant,int $productId,?int $variantId,string $source,?float $price,?int $stock){
    Database::pdo()->prepare("INSERT INTO reconcile_snapshots (tenant_id,product_id,variant_id,source,price,stock) VALUES (?,?,?,?,?,?)")
      ->execute([$tenant,$productId,$variantId,$source,$price,$stock]);
  }
}
