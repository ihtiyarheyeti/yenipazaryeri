<?php
namespace App\Standardizer;
use App\Database;
use App\Utils\Sku;

final class Standardizer {
  /** CanonicalProduct → DB upsert (products/variants), mapping/policy uygular */
  public static function upsert(CanonicalProduct $cp, int $tenant): array {
    $pdo=Database::pdo();

    // ürün
    $st=$pdo->prepare("SELECT id FROM products WHERE origin_mp=? AND origin_external_id=? LIMIT 1");
    $st->execute([$cp->origin,$cp->origin_id]); 
    $pid=(int)($st->fetchColumn()?:0);

    $localCat = Mapper::categoryToLocal($cp->origin, $cp->category_external, $tenant);

    if(!$pid){
      $pdo->prepare("INSERT INTO products (tenant_id,name,brand,description,category_path,origin_mp,origin_external_id,category_match,status,created_at,updated_at)
      VALUES (?,?,?,?,?,?,?, ?, 'active', NOW(),NOW())")
        ->execute([$tenant,$cp->name,$cp->brand,$cp->description,$localCat,$cp->origin,$cp->origin_id, $localCat?'mapped':'unmapped']);
      $pid=(int)$pdo->lastInsertId();
    }else{
      $pdo->prepare("UPDATE products SET name=?, brand=?, description=?, category_path=?, category_match=?, updated_at=NOW()
        WHERE id=?")
        ->execute([$cp->name,$cp->brand,$cp->description,$localCat,$localCat?'mapped':'unmapped',$pid]);
    }

    // varyantlar
    $ins=0;
    foreach($cp->variants as $v){
      $sku = $v['sku'] ?? null;
      $price = $v['price'] ?? null;
      $stock = $v['stock'] ?? null;
      $attrs = Mapper::normalizeAttrs($v['attrs'] ?? []);

      // Auto-SKU + upsert (ImportController'daki guard ile aynı mantık)
      $tst=$pdo->prepare("SELECT tenant_id,name FROM products WHERE id=?"); 
      $tst->execute([$pid]); 
      $prow=$tst->fetch();
      $tenantId=(int)$prow['tenant_id']; 
      $pname=(string)$prow['name'];
      $sku = ($sku && \App\Utils\Sku::isFree($tenantId,$sku)) ? $sku : Sku::ensure(substr($pname,0,10), $tenantId, $sku);

      $vv=$pdo->prepare("SELECT id FROM variants WHERE tenant_id=? AND sku=?");
      $vv->execute([$tenant,$sku]); 
      $vid=(int)($vv->fetchColumn()?:0);
      if(!$vid){
        $pdo->prepare("INSERT INTO variants (tenant_id,product_id,sku,price,stock,attrs_json,origin_mp,created_at,updated_at)
          VALUES (?,?,?,?,?,?,?,NOW(),NOW())")
          ->execute([$tenant,$pid,$sku,$price,$stock,json_encode($attrs,JSON_UNESCAPED_UNICODE),$cp->origin]);
        $ins++;
      }else{
        $pdo->prepare("UPDATE variants SET product_id=?, price=?, stock=?, attrs_json=?, origin_mp=?, updated_at=NOW() WHERE id=?")
          ->execute([$pid,$price,$stock,json_encode($attrs,JSON_UNESCAPED_UNICODE),$cp->origin,$vid]);
      }
    }
    return ['ok'=>true,'product_id'=>$pid,'variants_inserted'=>$ins];
  }
}

