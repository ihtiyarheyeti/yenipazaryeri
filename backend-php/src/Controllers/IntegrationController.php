<?php
namespace App\Controllers;
use App\Database;

final class IntegrationController {

  // Ürünü Trendyol'a göndermek için işi kuyruğa at
  public function sendTrendyol(array $p, array $b, array $q): array {
    $productId=(int)($p[0]??0); $tenant=(int)($q['tenant_id']??1);
    if($productId<=0) return ['ok'=>false,'error'=>'invalid product id'];

    // Aktif bağlantıyı al (marketplace_id=1 Trendyol varsaydık)
    $conn=self::findConnection($tenant,1);
    if(!$conn) return ['ok'=>false,'error'=>'connection_not_found'];

    // Job payload hazırlayıp kuyruğa at
    $payload=[
      'tenant_id'=>$tenant, 'product_id'=>$productId, 'marketplace_id'=>1,
      'conn'=>$conn
    ];
    Database::pdo()->prepare("INSERT INTO jobs (type,payload,status) VALUES ('push_trendyol',?, 'pending')")
                   ->execute([json_encode($payload)]);
    Database::pdo()->prepare("INSERT INTO logs (tenant_id,product_id,type,status,message) VALUES (?,?,?,?,?)")
                   ->execute([$tenant,$productId,'trendyol_push','queued','queued']);
    return ['ok'=>true];
  }

  // Ürünü WooCommerce'a göndermek için işi kuyruğa at
  public function sendWoo(array $p, array $b, array $q): array {
    $productId=(int)($p[0]??0); $tenant=(int)($q['tenant_id']??1);
    if($productId<=0) return ['ok'=>false,'error'=>'invalid product id'];

    // marketplace_id=2 Woo
    $conn=self::findConnection($tenant,2);
    if(!$conn) return ['ok'=>false,'error'=>'connection_not_found'];

    $payload=[
      'tenant_id'=>$tenant, 'product_id'=>$productId, 'marketplace_id'=>2,
      'conn'=>$conn
    ];
    Database::pdo()->prepare("INSERT INTO jobs (type,payload,status) VALUES ('push_woo',?, 'pending')")
                   ->execute([json_encode($payload)]);
    Database::pdo()->prepare("INSERT INTO logs (tenant_id,product_id,type,status,message) VALUES (?,?,?,?,?)")
                   ->execute([$tenant,$productId,'woo_push','queued','queued']);
    return ['ok'=>true];
  }

  private static function findConnection(int $tenant,int $marketplaceId){
    $st=Database::pdo()->prepare("SELECT c.*, m.base_url FROM marketplace_connections c JOIN marketplaces m ON m.id=c.marketplace_id WHERE c.tenant_id=? AND c.marketplace_id=? ORDER BY c.id DESC LIMIT 1");
    $st->execute([$tenant,$marketplaceId]);
    return $st->fetch();
  }

  // Ürün loglarını çekmek için küçük yardımcı (frontend'te göstereceğiz)
  public function productLogs(array $p, array $b, array $q): array {
    $id=(int)($p[0]??0);
    $st=Database::pdo()->prepare("SELECT id,type,status,message,created_at FROM logs WHERE product_id=? ORDER BY id DESC LIMIT 100");
    $st->execute([$id]);
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }

  // A) TEK ÜRÜN FİYAT/STOK SYNC (mapping zorunlu)
  public function syncTrendyol(array $p, array $b, array $q): array {
    $productId=(int)($p[0]??0); $tenant=(int)($q['tenant_id']??1);
    if($productId<=0) return ['ok'=>false,'error'=>'invalid product id'];
    $conn=self::findConnection($tenant,1); if(!$conn) return ['ok'=>false,'error'=>'connection_not_found'];

    // mapping kontrol
    $m=\App\Database::pdo()->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=1");
    $m->execute([$productId]); $ext=$m->fetchColumn();
    if(!$ext) return ['ok'=>false,'error'=>'not_mapped'];

    $payload=['tenant_id'=>$tenant,'product_id'=>$productId,'marketplace_id'=>1,'conn'=>$conn,'external_id'=>$ext];
    \App\Database::pdo()->prepare("INSERT INTO jobs (type,payload,status) VALUES ('sync_trendyol',?, 'pending')")->execute([json_encode($payload)]);
    \App\Database::pdo()->prepare("INSERT INTO logs (tenant_id,product_id,type,status,message) VALUES (?,?,?,?,?)")
      ->execute([$tenant,$productId,'trendyol_sync','queued','queued']);
    return ['ok'=>true];
  }

  public function syncWoo(array $p, array $b, array $q): array {
    $productId=(int)($p[0]??0); $tenant=(int)($q['tenant_id']??1);
    if($productId<=0) return ['ok'=>false,'error'=>'invalid product id'];
    $conn=self::findConnection($tenant,2); if(!$conn) return ['ok'=>false,'error'=>'connection_not_found'];

    $m=\App\Database::pdo()->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=2");
    $m->execute([$productId]); $ext=$m->fetchColumn();
    if(!$ext) return ['ok'=>false,'error'=>'not_mapped'];

    $payload=['tenant_id'=>$tenant,'product_id'=>$productId,'marketplace_id'=>2,'conn'=>$conn,'external_id'=>$ext];
    \App\Database::pdo()->prepare("INSERT INTO jobs (type,payload,status) VALUES ('sync_woo',?, 'pending')")->execute([json_encode($payload)]);
    \App\Database::pdo()->prepare("INSERT INTO logs (tenant_id,product_id,type,status,message) VALUES (?,?,?,?,?)")
      ->execute([$tenant,$productId,'woo_sync','queued','queued']);
    return ['ok'=>true];
  }

  // B) TOPLU GÖNDERİM (ID listesi) → push_trendyol / push_woo job'ları
  public function bulkSend(array $p, array $b): array {
    $tenant=(int)($b['tenant_id']??1);
    $ids = $b['product_ids'] ?? [];
    if(!is_array($ids) || count($ids)===0) return ['ok'=>false,'error'=>'empty_ids'];

    $mp = (int)($b['marketplace_id']??0); if(!in_array($mp,[1,2],true)) return ['ok'=>false,'error'=>'invalid_marketplace'];
    $conn=self::findConnection($tenant,$mp); if(!$conn) return ['ok'=>false,'error'=>'connection_not_found'];

    $jobType = $mp===1 ? 'push_trendyol' : 'push_woo';
    $pdo=\App\Database::pdo(); $enq=0;
    foreach($ids as $pid){
      $pid=(int)$pid; if($pid<=0) continue;
      $payload=['tenant_id'=>$tenant,'product_id'=>$pid,'marketplace_id'=>$mp,'conn'=>$conn];
      $pdo->prepare("INSERT INTO jobs (type,payload,status) VALUES (?,?, 'pending')")->execute([$jobType,json_encode($payload)]);
      $pdo->prepare("INSERT INTO logs (tenant_id,product_id,type,status,message) VALUES (?,?,?,?,?)")->execute([$tenant,$pid, $mp===1?'trendyol_push':'woo_push','queued','queued']);
      $enq++;
    }
    return ['ok'=>true,'enqueued'=>$enq];
  }
}
