<?php
namespace App\Builders;
use App\Database;
use App\Utils\AttrMap;

final class TrendyolProductBuilder {
  /**
   * @return array [items] Trendyol API payload item dizisi
   */
  public static function buildItems(int $productId, int $tenant): array {
    $pdo = Database::pdo();
    $p = $pdo->prepare("SELECT * FROM products WHERE id=?"); 
    $p->execute([$productId]); 
    $prod = $p->fetch(); 
    if(!$prod) return [];
    
    $vars = $pdo->prepare("SELECT * FROM variants WHERE product_id=?"); 
    $vars->execute([$productId]); 
    $variants = $vars->fetchAll();

    // category mapping
    $catPath = is_string($prod['category_path']??null) ? $prod['category_path'] : null;
    $st = $pdo->prepare("SELECT external_id FROM category_mapping WHERE tenant_id=? AND marketplace_id=1 AND local_path=?");
    $st->execute([$tenant, $catPath]); 
    $tyCat = $st->fetchColumn();

    $items = [];
    foreach($variants as $v){
      $attrs = $v['attrs_json'] ? json_decode($v['attrs_json'], true) : [];
      $extAttrs = AttrMap::toExternal($attrs, 1); // mp=1 trendyol
      $items[] = [
        'barcode'     => $v['sku'],
        'title'       => $prod['name'],
        'productMainId' => (string)$prod['id'],
        'brand'       => $prod['brand'] ?: 'Generic',
        'categoryId'  => $tyCat ?: 0,
        'quantity'    => (int)$v['stock'],
        'stockCode'   => (string)$v['id'],
        'dimensionalWeight' => 1,
        'description' => $prod['description'] ?: '',
        'currencyType'=> 'TRY',
        'listPrice'   => (float)$v['price'],
        'salePrice'   => (float)$v['price'],
        'vatRate'     => 20,
        'images'      => [],  // istersen product_images tablosundan doldur
        'attributes'  => self::kv($extAttrs) // [{"attributeId":..., "attributeValueId":...}] yerine basit key/value. Gerekirse id bazlı yapıyı uyarlayın.
      ];
    }
    return $items;
  }

  private static function kv(array $assoc): array {
    $out = []; 
    foreach($assoc as $k => $v){ 
      $out[] = ['name' => $k, 'value' => (string)$v]; 
    } 
    return $out;
  }
}
