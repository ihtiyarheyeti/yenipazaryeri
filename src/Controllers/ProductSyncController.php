<?php
namespace App\Controllers;
use App\Database;

final class ProductSyncController {
  private function conn(int $tenant, int $mp){
    $st = Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_id=? ORDER BY id DESC LIMIT 1");
    $st->execute([$tenant, $mp]); 
    return $st->fetch();
  }

  public function pushTrendyol(array $p): array {
    $pid = (int)$p[0]; 
    $tenant = \App\Context::$tenantId;
    $conn = $this->conn($tenant, 1); 
    if(!$conn) return ['ok' => false, 'error' => 'conn_trendyol_missing'];
    
    $items = \App\Builders\TrendyolProductBuilder::buildItems($pid, $tenant); 
    if(!$items) return ['ok' => false, 'error' => 'no_items'];
    
    $ty = new \App\Integrations\TrendyolAdapter($conn);
    $r = $ty->createOrUpdateProducts($items);
    
    Database::pdo()->prepare("UPDATE products SET sync_trendyol_status=?, sync_trendyol_msg=? WHERE id=?")
      ->execute([$r['ok'] ? 'ok' : 'error', $r['ok'] ? 'ok' : ('HTTP '.$r['code']), $pid]);
    
    // Auto image push policy
    if($r['ok']) {
      $policy = \App\Utils\Policy::get('auto_image_push', $tenant);
      if(!empty($policy['enabled'])) {
        Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('upload_trendyol_images','pending',?,NOW())")->execute([json_encode(['product_id'=>$pid])]);
      }
    }
    
    return ['ok' => $r['ok'], 'code' => $r['code'] ?? 200];
  }

  public function pushWoo(array $p): array {
    $pid = (int)$p[0]; 
    $tenant = \App\Context::$tenantId;
    $conn = $this->conn($tenant, 2); 
    if(!$conn) return ['ok' => false, 'error' => 'conn_woo_missing'];
    
    // Woo product external id'yi mapping'ten bul
    $ext = Database::pdo()->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=2");
    $ext->execute([$pid]); 
    $wooPid = $ext->fetchColumn();
    if(!$wooPid) return ['ok' => false, 'error' => 'woo_product_missing'];

    // varyantları güncelle (price/stock)
    $vs = Database::pdo()->prepare("SELECT id,sku,price,stock,attrs_json FROM variants WHERE product_id=?");
    $vs->execute([$pid]); 
    $vars = $vs->fetchAll();
    $woo = new \App\Integrations\WooAdapter($conn);
    $okAll = true;
    
    foreach($vars as $v){
      // var external variation id var mı?
      $map = Database::pdo()->prepare("SELECT external_variant_id FROM variant_marketplace_mapping WHERE variant_id=? AND marketplace_id=2");
      $map->execute([$v['id']]); 
      $wooVid = $map->fetchColumn();
      
      if($wooVid){
        $r = $woo->updateVariation((string)$wooPid, (string)$wooVid, [
          "regular_price" => (string)$v['price'],
          "stock_quantity" => (int)$v['stock']
        ]);
        if(!$r['ok']) $okAll = false;
      } else {
        // oluşturma kuyruğuna bırak
        Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('create_woo_variations','pending',?,NOW())")
          ->execute([json_encode(['product_id' => $pid])]);
        $okAll = false; // mapping tamamlanana kadar partial say
      }
    }
    
    Database::pdo()->prepare("UPDATE products SET sync_woo_status=?, sync_woo_msg=? WHERE id=?")
      ->execute([$okAll ? 'ok' : 'queued', $okAll ? 'ok' : 'create_variations_enqueued', $pid]);
    
    return ['ok' => true, 'queued' => !$okAll];
  }

  public function createWooVariationsJob(array $p): array {
    $pid = (int)($p[0] ?? 0); 
    $tenant = \App\Context::$tenantId;
    $conn = $this->conn($tenant, 2); 
    if(!$conn) return ['ok' => false, 'error' => 'conn_woo_missing'];
    
    $woo = new \App\Integrations\WooAdapter($conn);

    // Woo product id
    $ext = Database::pdo()->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=2");
    $ext->execute([$pid]); 
    $wooPid = $ext->fetchColumn(); 
    if(!$wooPid) return ['ok' => false, 'error' => 'woo_product_missing'];

    // Var olan variations'ı çek
    $existing = $woo->listVariations((string)$wooPid);
    $bySku = []; 
    foreach($existing as $e){ 
      if(!empty($e['sku'])) $bySku[$e['sku']] = $e['id']; 
    }

    // Local varyantlar
    $vs = Database::pdo()->prepare("SELECT id,sku,price,stock,attrs_json FROM variants WHERE product_id=?");
    $vs->execute([$pid]); 
    $vars = $vs->fetchAll();

    $created = 0;
    foreach($vars as $v){
      if(!$v['sku']) continue;
      
      if(isset($bySku[$v['sku']])){
        // mapping ekle
        Database::pdo()->prepare("INSERT IGNORE INTO variant_marketplace_mapping(variant_id,marketplace_id,external_variant_id) VALUES (?,?,?)")
          ->execute([$v['id'], 2, (string)$bySku[$v['sku']]]);
        continue;
      }
      
      // yeni variation oluştur
      $attrs = $v['attrs_json'] ? json_decode($v['attrs_json'], true) : [];
      $payload = [
        "sku" => $v['sku'],
        "regular_price" => (string)$v['price'],
        "stock_quantity" => (int)$v['stock'],
        "attributes" => self::wooAttrs($attrs) // [{name:'Color', option:'Red'}, ...]
      ];
      
      $res = $woo->createVariation((string)$wooPid, $payload);
      if(!empty($res['ok']) && !empty($res['data']['id'])){
        Database::pdo()->prepare("INSERT IGNORE INTO variant_marketplace_mapping(variant_id,marketplace_id,external_variant_id) VALUES (?,?,?)")
          ->execute([$v['id'], 2, (string)$res['data']['id']]);
        $created++;
      }
    }
    
    return ['ok' => true, 'created' => $created];
  }

  private static function wooAttrs(array $attrs): array {
    $out = []; 
    foreach($attrs as $k => $v){ 
      $out[] = ['name' => $k, 'option' => (string)$v]; 
    } 
    return $out;
  }
}
