<?php
namespace App\Controllers\Webhooks;
use App\Database;

final class WooWebhookController {
  // Woo'dan gelen JSON'da sku/stock/price bilgisi bulunduğunu varsayalım
  public function productUpdated(array $p, array $b): array {
    $sku=$b['sku']??null; 
    if(!$sku) return ['ok'=>false,'error'=>'no_sku'];
    
    $st=\App\Database::pdo()->prepare("SELECT v.*, p.tenant_id FROM variants v JOIN products p ON p.id=v.product_id WHERE v.sku=? LIMIT 1");
    $st->execute([$sku]); 
    $v=$st->fetch(); 
    if(!$v) return ['ok'=>true]; // yoksa sessizce geç
    
    // Job kuyruğuna "sync_trendyol" at (bizdeki prod/variant state'i Trendyol'a bas)
    \App\Database::pdo()->prepare("INSERT INTO jobs (type,status,payload,created_at) VALUES ('sync_trendyol','pending',?,NOW())")
      ->execute([json_encode(['product_id'=>$v['product_id']])]);
    
    return ['ok'=>true];
  }
}
