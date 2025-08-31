<?php
namespace App\Controllers;
use App\Database;

final class ImageSyncController {
  private static function getProduct(int $id){ $p=Database::pdo()->prepare("SELECT * FROM products WHERE id=?"); $p->execute([$id]); return $p->fetch(); }
  private static function getImages(int $pid){ $s=Database::pdo()->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC,id ASC"); $s->execute([$pid]); return $s->fetchAll(); }
  private static function mapping(int $pid,int $mp){ $s=Database::pdo()->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=?"); $s->execute([$pid,$mp]); return $s->fetchColumn(); }

  public function syncWoo(array $p, array $b, array $q): array {
    $pid=(int)($p[0]??0); $tenant=(int)($q['tenant_id']??1);
    if($pid<=0) return ['ok'=>false,'error'=>'invalid_id'];
    $ext=self::mapping($pid,2); if(!$ext) return ['ok'=>false,'error'=>'not_mapped'];
    $imgs=self::getImages($pid); if(!$imgs) return ['ok'=>false,'error'=>'no_images'];

    // connection
    $st=Database::pdo()->prepare("SELECT c.*, m.base_url FROM marketplace_connections c JOIN marketplaces m ON m.id=c.marketplace_id WHERE c.tenant_id=? AND c.marketplace_id=2 ORDER BY c.id DESC LIMIT 1");
    $st->execute([$tenant]); $conn=$st->fetch(); if(!$conn) return ['ok'=>false,'error'=>'connection_not_found'];

    $base = rtrim(getenv('APP_BASE_URL')?:'', '/');
    $urls = array_map(fn($x)=> (strpos($x['url'],'http')===0? $x['url'] : $base.($x['thumb_url'] ?: $x['url'])), $imgs);
    $adapter=new \App\Integrations\WooAdapter($conn);
    $res=$adapter->addImagesToProduct((string)$ext, $urls);
    if(!$res['ok']){ \App\Database::pdo()->prepare("INSERT INTO logs (product_id,type,status,message) VALUES (?,?,?,?)")->execute([$pid,'woo_image','error','http_'.$res['code']]); return ['ok'=>false,'error'=>'woo_http_'.$res['code']]; }

    Database::pdo()->prepare("UPDATE product_images SET synced_to_woo=1,last_synced_at=NOW() WHERE product_id=?")->execute([$pid]);
    \App\Database::pdo()->prepare("INSERT INTO logs (product_id,type,status,message) VALUES (?,?,?,?)")->execute([$pid,'woo_image','success','synced']);
    return ['ok'=>true];
  }

  public function syncTrendyol(array $p, array $b, array $q): array {
    $pid=(int)($p[0]??0); $tenant=(int)($q['tenant_id']??1);
    if($pid<=0) return ['ok'=>false,'error'=>'invalid_id'];
    $ext=self::mapping($pid,1); if(!$ext) return ['ok'=>false,'error'=>'not_mapped'];
    $imgs=self::getImages($pid); if(!$imgs) return ['ok'=>false,'error'=>'no_images'];

    $st=Database::pdo()->prepare("SELECT c.*, m.base_url FROM marketplace_connections c JOIN marketplaces m ON m.id=c.marketplace_id WHERE c.tenant_id=? AND c.marketplace_id=1 ORDER BY c.id DESC LIMIT 1");
    $st->execute([$tenant]); $conn=$st->fetch(); if(!$conn) return ['ok'=>false,'error'=>'connection_not_found'];

    $base = rtrim(getenv('APP_BASE_URL')?:'', '/');
    $urls = array_map(fn($x)=> (strpos($x['url'],'http')===0? $x['url'] : $base.($x['thumb_url'] ?: $x['url'])), $imgs);
    $adapter=new \App\Integrations\TrendyolAdapter($conn);
    $res=$adapter->addImagesToProduct((string)$ext, $urls);
    if(!$res['ok']){ \App\Database::pdo()->prepare("INSERT INTO logs (product_id,type,status,message) VALUES (?,?,?,?)")->execute([$pid,'trendyol_image','error','http_'.$res['code']]); return ['ok'=>false,'error'=>'ty_http_'.$res['code']]; }

    Database::pdo()->prepare("UPDATE product_images SET synced_to_ty=1,last_synced_at=NOW() WHERE product_id=?")->execute([$pid]);
    \App\Database::pdo()->prepare("INSERT INTO logs (product_id,type,status,message) VALUES (?,?,?,?)")->execute([$pid,'trendyol_image','success','synced']);
    return ['ok'=>true];
  }
}
