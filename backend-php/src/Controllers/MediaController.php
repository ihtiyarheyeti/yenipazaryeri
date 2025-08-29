<?php
namespace App\Controllers;
use App\Database;

final class MediaController {
  public function fetchFromWoo(array $p): array {
    $pid=(int)$p[0]; $tenant=\App\Context::$tenantId;
    // woo product id mapping
    $st=Database::pdo()->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=2");
    $st->execute([$pid]); $wooId=$st->fetchColumn(); if(!$wooId) return ['ok'=>false,'error'=>'woo_product_missing'];
    $conn=$this->conn(2); if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];
    $woo=new \App\Integrations\WooAdapter($conn);
    $imgs=$woo->listProductImages((string)$wooId);
    $n=0; foreach($imgs as $i){
      $url=$i['src'] ?? null; if(!$url) continue;
      Database::pdo()->prepare("INSERT INTO images(product_id,url,position,status,created_at,updated_at) VALUES (?,?,?, 'ready', NOW(),NOW()) ON DUPLICATE KEY UPDATE updated_at=NOW()")
        ->execute([$pid,$url,(int)($i['position']??0)]);
      $n++;
    }
    // ürün medya durumu
    Database::pdo()->prepare("UPDATE products SET media_status=? WHERE id=?")->execute([$n>0?'ready':'none',$pid]);
    return ['ok'=>true,'count'=>$n];
  }

  public function pushToTrendyol(array $p): array {
    $pid=(int)$p[0]; $tenant=\App\Context::$tenantId;
    // TY product id
    $st=Database::pdo()->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=1");
    $st->execute([$pid]); $tyId=$st->fetchColumn(); if(!$tyId) return ['ok'=>false,'error'=>'ty_product_missing'];
    // image urls
    $rows=Database::pdo()->prepare("SELECT url FROM images WHERE product_id=? ORDER BY position ASC, id ASC LIMIT 8");
    $rows->execute([$pid]); $urls=array_column($rows->fetchAll(), 'url');
    if(!$urls) return ['ok'=>false,'error'=>'no_images'];
    $conn=$this->conn(1); if(!$conn) return ['ok'=>false,'error'=>'conn_ty_missing'];
    $ty=new \App\Integrations\TrendyolAdapter($conn);
    $res=$ty->uploadImages((string)$tyId, $urls);
    if($res['ok']) \App\Middleware\Audit::log(null,'media.ty.upload',"/products/$pid",['count'=>count($urls)]);
    return $res;
  }

  private function conn(int $mp){
    $st=\App\Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_id=? ORDER BY id DESC LIMIT 1");
    $st->execute([\App\Context::$tenantId,$mp]); return $st->fetch();
  }
}
