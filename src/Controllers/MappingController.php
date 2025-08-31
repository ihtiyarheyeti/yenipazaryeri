<?php
namespace App\Controllers;
use App\Database;

final class MappingController {
  private function connWoo(){
    $st=Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_id=2 ORDER BY id DESC LIMIT 1");
    $st->execute([\App\Context::$tenantId]); return $st->fetch();
  }

  /** POST /mapping/woo/resolve body:{ sku?:string, limit?:int } — boş bırakılırsa unmapped'lerden toplu */
  public function resolveWoo(array $p, array $b): array {
    try {
      $pdo=Database::pdo(); 
      $tenant=\App\Context::$tenantId;
      $conn=$this->connWoo(); 
      if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];
      
      $woo=new \App\Integrations\WooAdapter($conn);

      $limit = (int)($b['limit'] ?? 200);
      $rows = [];

      if (!empty($b['sku'])) {
        $st=$pdo->prepare("SELECT v.id as variant_id, v.sku, v.product_id FROM variants v WHERE v.tenant_id=? AND v.sku=? LIMIT 1");
        $st->execute([$tenant, trim($b['sku'])]); 
        $one=$st->fetch(); 
        if($one) $rows[]=$one;
      } else {
        // unmapped woo varyantları
        $st=$pdo->prepare("SELECT v.id as variant_id, v.sku, v.product_id
                           FROM variants v
                           LEFT JOIN variant_marketplace_mapping m ON m.variant_id=v.id AND m.marketplace_id=2
                           WHERE v.tenant_id=? AND v.sku IS NOT NULL AND m.id IS NULL
                           LIMIT $limit");
        $st->execute([$tenant]); 
        $rows=$st->fetchAll();
      }

      $ok=0; $fail=0;
      foreach($rows as $r){
        $sku=$r['sku']; 
        if(!$sku) { $fail++; continue; }
        
        // 1) SKU ile ürün ara
        $cands = $woo->findProductBySku($sku);
        $foundProdId = null; $foundVarId=null;

        foreach($cands as $pjson){
          $pid = (string)($pjson['id']??'');
          // 2) varyasyonlarda SKU eşleşmesi var mı?
          $vars = $woo->listVariations($pid,1,100) ?? [];
          foreach($vars as $vj){
            if(($vj['sku']??'') === $sku){
              $foundProdId=$pid; $foundVarId=(string)($vj['id']??''); break 2;
            }
          }
          // 3) simple ürün ise ürün SKU'su eşleşebilir
          if(($pjson['sku']??'') === $sku){ $foundProdId=$pid; break; }
        }

        if($foundProdId){
          $pdo->prepare("INSERT INTO variant_marketplace_mapping(variant_id,marketplace_id,external_product_id,external_variant_id)
                         VALUES (?,?,?,?)
                         ON DUPLICATE KEY UPDATE external_product_id=VALUES(external_product_id), external_variant_id=VALUES(external_variant_id)")
              ->execute([$r['variant_id'],2,$foundProdId,$foundVarId]);
          $ok++;
        } else { $fail++; }
      }
      
      return ['ok'=>true,'mapped'=>$ok,'unresolved'=>$fail];
      
    } catch (\Throwable $e) {
      error_log("MappingController::resolveWoo hatası: " . $e->getMessage());
      return ['ok'=>false,'error'=>'resolve_failed','details'=>$e->getMessage()];
    }
  }
}
