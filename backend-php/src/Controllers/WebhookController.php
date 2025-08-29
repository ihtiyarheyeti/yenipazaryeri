<?php
namespace App\Controllers;
use App\Database;

final class WebhookController {
  // Woo: price/stock değişiminde webhook → body JSON içinde SKU/stock/price varsayımı
  public function woo(array $p, array $b): array {
    \App\Database::pdo()->prepare("INSERT INTO webhook_events (marketplace_id,event_type,payload_json) VALUES (2,?,?)")
      ->execute([$b['event']??'unknown', json_encode($b)]);
    // Kuyruğa stok/fiyat senkronu
    $sku=$b['sku'] ?? null; $price=$b['price'] ?? null; $stock=$b['stock'] ?? null;
    \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('sync_price_stock','pending',?,NOW())")
      ->execute([json_encode(['source'=>'woo','sku'=>$sku,'price'=>$price,'stock'=>$stock])]);
    
    // Sipariş olayları
    if(($b['resource'] ?? '')==='order'){
      \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('pull_woo_orders','pending',JSON_OBJECT(),NOW())")->execute();
    }
    return ['ok'=>true];
  }
  
  // Trendyol: webhook payloadı formatları farklı olabilir; basit json pas geç
  public function trendyol(array $p, array $b): array {
    \App\Database::pdo()->prepare("INSERT INTO webhook_events (marketplace_id,event_type,payload_json) VALUES (1,?,?)")
      ->execute([$b['event']??'unknown', json_encode($b)]);
    $sku=$b['sku'] ?? null; $price=$b['price'] ?? null; $stock=$b['stock'] ?? null;
    \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('sync_price_stock','pending',?,NOW())")
      ->execute([json_encode(['source'=>'trendyol','sku'=>$sku,'price'=>$price,'stock'=>$stock])]);
    
    // Sipariş olayları
    if(($b['resource'] ?? '')==='order'){
      \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('pull_trendyol_orders','pending',JSON_OBJECT(),NOW())")->execute();
    }
    return ['ok'=>true];
  }
}
