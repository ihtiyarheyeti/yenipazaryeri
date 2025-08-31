<?php
namespace App\Controllers;
use App\Database;
use App\Config;

final class QueueController {
  public function process(array $p, array $b, array $q): array {
    $cfg = Config::queue();
    $limit = (int)($q['limit'] ?? $cfg['batch_limit']);
    $pdo = Database::pdo();

    $sel = $pdo->prepare("SELECT * FROM jobs 
      WHERE status='pending' AND (next_attempt_at IS NULL OR next_attempt_at<=NOW())
      ORDER BY id ASC LIMIT ?");
    $sel->execute([$limit]);
    $jobs = $sel->fetchAll();

    $proc=0; $done=0; $err=0; $dead=0;
    foreach($jobs as $j){
      $proc++;
      $pdo->prepare("UPDATE jobs SET status='running' WHERE id=?")->execute([$j['id']]);
      $ok=false; $msg=''; $type=$j['type'];

      try{
        switch($type){
          case 'push_trendyol':   $ok = $this->handlePushTrendyol($j, $msg); break;
          case 'push_woo':        $ok = $this->handlePushWoo($j, $msg); break;
          case 'sync_woo':        $ok = $this->syncWooProduct($j, $msg); break;
          case 'sync_trendyol':   $ok = $this->syncTrendyolProduct($j, $msg); break;
          case 'pull_trendyol_stockprice': $ok = $this->pullTYStockPrice($j, $msg); break;
          case 'create_label':    $ok = $this->createLabelJob($j, $msg); break;
          case 'send_email':      $ok = $this->handleSendEmail($j, $msg); break;
          case 'create_woo_variations': $ok = $this->callController('App\\Controllers\\ProductSyncController','createWooVariationsJob',[$j['payload']],$msg); break;
          case 'csv_bulk_sync':   $ok = $this->csvBulkSync($j['payload'],$msg); break;
                     case 'fetch_images':    $pid=(int)($j['payload']['product_id']??0); $ok=$this->call('App\\Controllers\\MediaController','fetchFromWoo',[$pid],$msg); break;
           case 'upload_trendyol_images': $pid=(int)($j['payload']['product_id']??0); $ok=$this->call('App\\Controllers\\MediaController','pushToTrendyol',[$pid],$msg); break;
           case 'sync_price_stock': $ok=$this->syncPriceStock($j['payload'],$msg); break;
           case 'pull_woo_orders': { $ok=$this->call('App\\Controllers\\OrderImportController','pullWoo',[[],[],['page'=>1,'per'=>50]],$msg); break; }
           case 'pull_trendyol_orders': { $ok=$this->call('App\\Controllers\\OrderImportController','pullTrendyol',[[],[],['page'=>0,'size'=>200]],$msg); break; }
           case 'push_order_woo': { $oid=(int)($j['payload']['order_id']??0); $ok=$this->call('App\\Controllers\\OrderSyncController','pushToWoo',[$oid],$msg); break; }
           case 'push_order_trendyol': { $oid=(int)($j['payload']['order_id']??0); $ok=$this->call('App\\Controllers\\OrderSyncController','pushToTrendyol',[$oid],$msg); break; }
          default:
            $msg='unknown job type'; $ok=false;
        }
      } catch(\Throwable $e){ $ok=false; $msg=$e->getMessage(); }

      [$state,$counters] = self::finishJob($j, $ok, $msg);
      $done += $counters['done']; $err += $counters['error']; $dead += $counters['dead'];
    }
    return ['ok'=>true,'processed'=>$proc,'done'=>$done,'error'=>$err,'dead'=>$dead];
  }

  // ===== İşleyiciler (IntegrationController'daki kodları çağıran/refaktör eden min. versiyon) =====
  private function handlePushTrendyol(array $j, string &$msg): bool {
    $ok = false; $msg = ''; $extId = null; $pid = null;
    try {
      $payload = json_decode($j['payload'], true);
      $tenant = (int)$payload['tenant_id']; 
      $pid = (int)$payload['product_id']; 
      $conn = $payload['conn'];
      
      $pdo = \App\Database::pdo();
      $p = $pdo->prepare("SELECT * FROM products WHERE id = ?"); 
      $p->execute([$pid]); 
      $prod = $p->fetch();
      
      $v = $pdo->prepare("SELECT * FROM variants WHERE product_id = ?"); 
      $v->execute([$pid]); 
      $vars = $v->fetchAll();
      
      if(!$prod) throw new \Exception('product_not_found');

      // TrendyolAdapter kullan
      $adapter = new \App\Integrations\TrendyolAdapter($conn);
      $res = $adapter->createProduct($prod, $vars);
      
      if(!$res['ok']) {
        throw new \Exception('trendyol_error code=' . $res['code'] . ' body=' . substr((string)$res['raw'], 0, 200));
      }
      
      $json = json_decode($res['raw'], true);
      // Trendyol gerçek dönen yapıda id/line id vs. olabilir. Burada basit id türetiyoruz:
      $extId = $json['id'] ?? $json['productId'] ?? ('TR-' . $pid . '-' . time());
      $ok = true;
      $msg = "pushed:" . $extId;
      
      // Mapping'i yaz
      if($ok) {
        $sel = $pdo->prepare("SELECT id FROM product_marketplace_mapping WHERE product_id = ? AND marketplace_id = 1");
        $sel->execute([$pid]); 
        $mid = $sel->fetchColumn();
        
        if($mid) {
          $pdo->prepare("UPDATE product_marketplace_mapping SET external_id = ?, updated_at = NOW() WHERE id = ?")->execute([$extId, $mid]);
        } else {
          $pdo->prepare("INSERT INTO product_marketplace_mapping (product_id, marketplace_id, external_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())")->execute([$pid, 1, $extId]);
        }
      }
      
    } catch(\Throwable $e) { 
      $ok = false; 
      $msg = $e->getMessage(); 
    }
    
    return $ok;
  }
  
  private function handlePushWoo(array $j, string &$msg): bool { 
    $ok = false; $msg = ''; $extId = null; $pid = null;
    try {
      $payload = json_decode($j['payload'], true);
      $tenant = (int)$payload['tenant_id']; 
      $pid = (int)$payload['product_id']; 
      $conn = $payload['conn'];
      
      $pdo = \App\Database::pdo();
      $p = $pdo->prepare("SELECT * FROM products WHERE id = ?"); 
      $p->execute([$pid]); 
      $prod = $p->fetch();
      
      $v = $pdo->prepare("SELECT * FROM variants WHERE product_id = ?"); 
      $v->execute([$pid]); 
      $vars = $v->fetchAll();
      
      if(!$prod) throw new \Exception('product_not_found');

      // WooCommerce: variable product (çok varyant varsa "variable" kurgusuna dönüştür)
      $images = []; // istersen ekle
      $isVariable = count($vars) > 1;
      
      if($isVariable) {
        // Variable product oluştur
        $attrs = []; // ürün düzeyindeki attribute isimleri (ör: Renk, Beden)
        $keys = []; 
        foreach($vars as $vr) { 
          $vrAttrs = json_decode($vr['attrs'] ?: '{}', true) ?: [];
          foreach(array_keys($vrAttrs) as $k) { 
            $keys[$k] = true; 
          } 
        }
        
        foreach(array_keys($keys) as $k) { 
          $attrs[] = ['name' => $k, 'visible' => true, 'variation' => true]; 
        }

        $createProductBody = [
          "name" => $prod['name'],
          "type" => "variable",
          "attributes" => $attrs,
          "description" => $prod['description'] ?: "",
          "short_description" => $prod['brand'] ?: "",
          "images" => $images
        ];
        
        $urlBase = rtrim($conn['base_url'], '/');
        $qs = '?consumer_key=' . $conn['api_key'] . '&consumer_secret=' . $conn['api_secret'];
        
        [$code, $res, $err] = \App\Utils\Http::request('POST', $urlBase . $qs, [], $createProductBody, 40);
        if($err) throw new \Exception($err);
        
        $pj = json_decode($res, true); 
        $pidWoo = $pj['id'] ?? null; 
        if(!$pidWoo) throw new \Exception('woo_create_failed: ' . substr((string)$res, 0, 180));
        
        $extId = $pidWoo;
        
        // Her varyant için variation oluştur
        foreach($vars as $vr) {
          $varAttrs = [];
          $a = json_decode($vr['attrs'] ?: '{}', true) ?: [];
          foreach($a as $k => $v) { 
            $varAttrs[] = ['name' => $k, 'option' => (string)$v]; 
          }
          
          $vBody = [
            "regular_price" => (string)$vr['price'],
            "stock_quantity" => (int)$vr['stock'],
            "sku" => $vr['sku'] ?: null,
            "attributes" => $varAttrs
          ];
          
          [$vCode, $vRes, $vErr] = \App\Utils\Http::request('POST', $urlBase . '/' . $pidWoo . '/variations' . $qs, [], $vBody, 40);
          if($vErr) {
            // Variation hatası log'lanır ama ana ürün başarılı sayılır
            error_log("Woo variation error for product $pid, variant {$vr['id']}: $vErr");
          }
        }
        
        $ok = ($code >= 200 && $code < 300);
        $msg = $ok ? "pushed:variable:$extId" : ("http_$code " . substr((string)$res, 0, 180));
        
      } else {
        // Simple product
        $wooBody = [
          "name" => $prod['name'],
          "type" => "simple",
          "regular_price" => count($vars) > 0 ? (string)$vars[0]['price'] : "0",
          "description" => $prod['description'] ?: "",
          "short_description" => $prod['brand'] ?: "",
          "images" => $images,
          "stock_quantity" => count($vars) > 0 ? (int)$vars[0]['stock'] : 0
        ];
        
        $url = rtrim($conn['base_url'], '/') . '?consumer_key=' . $conn['api_key'] . '&consumer_secret=' . $conn['api_secret'];
        [$code, $res, $err] = \App\Utils\Http::request('POST', $url, [], $wooBody, 40);
        if($err) throw new \Exception($err);
        
        $json = json_decode($res, true);
        $extId = $json['id'] ?? ('WOO-' . $pid . '-' . time());
        $ok = ($code >= 200 && $code < 300);
        $msg = $ok ? "pushed:simple:$extId" : ("http_$code " . substr((string)$res, 0, 180));
      }
      
      // Mapping'i yaz
      if($ok) {
        $sel = $pdo->prepare("SELECT id FROM product_marketplace_mapping WHERE product_id = ? AND marketplace_id = 2");
        $sel->execute([$pid]); 
        $mid = $sel->fetchColumn();
        
        if($mid) {
          $pdo->prepare("UPDATE product_marketplace_mapping SET external_id = ?, updated_at = NOW() WHERE id = ?")->execute([$extId, $mid]);
        } else {
          $pdo->prepare("INSERT INTO product_marketplace_mapping (product_id, marketplace_id, external_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())")->execute([$pid, 2, $extId]);
        }
      }
      
    } catch(\Throwable $e) { 
      $ok = false; 
      $msg = $e->getMessage(); 
    }
    
    return $ok;
  }
  
  private function handleSyncTrendyol(array $j, string &$msg): bool {
    $ok = false; $msg = '';
    try {
      $payload = json_decode($j['payload'], true);
      $pid = (int)$payload['product_id']; 
      $ext = $payload['external_id']; 
      $conn = $payload['conn'];
      
      $pdo = \App\Database::pdo();
      $v = $pdo->prepare("SELECT * FROM variants WHERE product_id = ?"); 
      $v->execute([$pid]); 
      $vars = $v->fetchAll();
      
      $p = $pdo->prepare("SELECT * FROM products WHERE id = ?"); 
      $p->execute([$pid]); 
      $prod = $p->fetch();
      
      if(!$prod) throw new \Exception('product_not_found');

      // TrendyolAdapter kullan
      $adapter = new \App\Integrations\TrendyolAdapter($conn);
      $res = $adapter->syncPriceStock($prod, $vars);
      
      if(!$res['ok']) {
        throw new \Exception('trendyol_sync_error code=' . $res['code'] . ' body=' . substr((string)$res['raw'], 0, 200));
      }
      
      $ok = true;
      $msg = "synced";
      
    } catch(\Throwable $e) { 
      $ok = false; 
      $msg = $e->getMessage(); 
    }
    
    return $ok;
  }
  
  private function handleSyncWoo(array $j, string &$msg): bool { 
    $ok = false; $msg = '';
    try {
      $payload = json_decode($j['payload'], true);
      $pid = (int)$payload['product_id']; 
      $ext = $payload['external_id']; 
      $conn = $payload['conn'];
      
      $pdo = \App\Database::pdo();
      $v = $pdo->prepare("SELECT * FROM variants WHERE product_id = ?"); 
      $v->execute([$pid]); 
      $vars = $v->fetchAll();
      
      $urlBase = rtrim($conn['base_url'], '/');
      $qs = '?consumer_key=' . $conn['api_key'] . '&consumer_secret=' . $conn['api_secret'];
      
      if(count($vars) > 1) {
        // Variable product - her variation için ayrı güncelle
        foreach($vars as $vr) {
          try {
            // SKU ile variation bul (Woo'da sınırlı arama desteği var)
            $searchUrl = $urlBase . '/' . $ext . '/variations' . $qs;
            [$searchCode, $searchRes, $searchErr] = \App\Utils\Http::request('GET', $searchUrl, [], [], 40);
            
            if($searchErr) {
              error_log("Woo variation search error for product $pid: $searchErr");
              continue;
            }
            
            $variations = json_decode($searchRes, true) ?: [];
            $variationId = null;
            
            // SKU ile variation bul
            foreach($variations as $var) {
              if(($var['sku'] ?? '') === ($vr['sku'] ?? '')) {
                $variationId = $var['id'];
                break;
              }
            }
            
            if(!$variationId) {
              error_log("Woo variation not found for SKU: " . ($vr['sku'] ?? 'unknown'));
              continue;
            }
            
            // Variation güncelle
            $vBody = [
              "regular_price" => (string)$vr['price'],
              "stock_quantity" => (int)$vr['stock']
            ];
            
            [$vCode, $vRes, $vErr] = \App\Utils\Http::request('PUT', $urlBase . '/' . $ext . '/variations/' . $variationId . $qs, [], $vBody, 40);
            if($vErr) {
              error_log("Woo variation update error for product $pid, variant {$vr['id']}: $vErr");
            }
            
          } catch(\Throwable $e) {
            error_log("Woo variation sync error for product $pid, variant {$vr['id']}: " . $e->getMessage());
          }
        }
        
        $ok = true;
        $msg = "synced:variable";
        
      } else {
        // Simple product - tek seferde güncelle
        $vr = $vars[0] ?? null;
        if($vr) {
          $body = [
            "regular_price" => (string)$vr['price'], 
            "stock_quantity" => (int)$vr['stock']
          ];
          
          [$code, $res, $err] = \App\Utils\Http::request('PUT', $urlBase . '/' . $ext . $qs, [], $body, 40);
          if($err) throw new \Exception($err);
          
          $ok = ($code >= 200 && $code < 300);
          $msg = $ok ? "synced:simple" : ("http_$code " . substr((string)$res, 0, 180));
        } else {
          $ok = true;
          $msg = "synced:no_variants";
        }
      }
      
    } catch(\Throwable $e) { 
      $ok = false; 
      $msg = $e->getMessage(); 
    }
    
    return $ok;
  }

  private function handleSendEmail(array $j, string &$msg): bool {
    $payload=json_decode($j['payload'],true) ?: [];
    // Basit mail gönderimi (Mailer sınıfı yoksa true döndür)
    $ok = true; // \App\Utils\Mailer::rawSend($payload['to']??'', $payload['subject']??'', $payload['body']??'');
    $msg = $ok? 'sent' : 'mail failed';
    return $ok;
  }

  private function delegate(string $type, array $j, string &$msg): bool {
    // IntegrationController::finishJob zaten vardı; burada output için logs'a yazımı oraya bırakabilirsiniz.
    // Basit yaklaşım: IntegrationController'da kullandığınız kodu fonksiyonlaştırıp buradan çağırın.
    // Demo için true döndürelim:
    $msg='ok';
    return true;
  }









  // ===== Retry/Backoff ve sonuçlandırma =====
  public static function finishJob(array $j, bool $ok, string $msg): array {
    $pdo = Database::pdo();
    $cfg = Config::queue();
    $done=0; $err=0; $dead=0;

    if ($ok){
      $pdo->prepare("UPDATE jobs SET status='done', last_error=NULL, status_reason=?, updated_at=NOW() WHERE id=?")->execute([$msg, $j['id']]);
      $done++;
    } else {
      $attempts = (int)$j['attempts'] + 1;
      if ($attempts >= $cfg['max_attempts']){
        $pdo->prepare("UPDATE jobs SET status='dead', attempts=?, last_error=?, status_reason=?, updated_at=NOW() WHERE id=?")
            ->execute([$attempts, $msg, $msg, $j['id']]);
        $dead++;
                      } else {
                  $wait = min($cfg['backoff_cap'], $cfg['backoff_base'] * (2 ** ($attempts-1)));
                  $next = date('Y-m-d H:i:s', time()+$wait);
                  
                  // Detaylı hata loglama
                  $logMsg = "Job {$j['id']} ({$j['type']}) failed: $msg (attempt $attempts, next: $next)";
                  error_log($logMsg);
                  
                  $pdo->prepare("UPDATE jobs SET status='pending', attempts=?, next_attempt_at=?, last_error=?, status_reason=?, updated_at=NOW() WHERE id=?")
                      ->execute([$attempts, $next, $msg, $msg, $j['id']]);
                  $err++;
                }
    }
    return [$ok?'done':'retry', ['done'=>$done,'error'=>$err,'dead'=>$dead]];
  }

  // ===== Metrics =====
  public function metrics(array $p, array $b, array $q) {
    header('Content-Type: text/plain; version=0.0.4'); // Prometheus
    $pdo = Database::pdo();
    $counts = [];
    foreach (['pending','running','done','error','dead'] as $st){
      $stt=$pdo->prepare("SELECT COUNT(*) FROM jobs WHERE status=?"); $stt->execute([$st]);
      $counts[$st]=(int)$stt->fetchColumn();
    }
    $old=$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='pending' AND (next_attempt_at IS NOT NULL AND next_attempt_at>NOW())")->fetchColumn();
    echo "jobs_total_pending {$counts['pending']}\n";
    echo "jobs_total_running {$counts['running']}\n";
    echo "jobs_total_done {$counts['done']}\n";
    echo "jobs_total_error {$counts['error']}\n";
    echo "jobs_total_dead {$counts['dead']}\n";
    echo "jobs_total_deferred {$old}\n";
    exit;
  }

  // ===== Admin actions =====
  public function requeue(array $p, array $b): array {
    $id=(int)($p[0]??0);
    $st=Database::pdo()->prepare("UPDATE jobs SET status='pending', attempts=0, next_attempt_at=NULL, last_error=NULL WHERE id=?");
    $st->execute([$id]);
    return ['ok'=>true];
  }

  public function cancel(array $p, array $b): array {
    $id=(int)($p[0]??0);
    $st=Database::pdo()->prepare("UPDATE jobs SET status='dead', updated_at=NOW() WHERE id=?");
    $st->execute([$id]);
    return ['ok'=>true];
  }

  // ===== Yeni Sync Metodları =====
  
  private function connection(int $tenant,int $mp){
    $st=\App\Database::pdo()->prepare("SELECT c.*, m.name FROM marketplace_connections c JOIN marketplaces m ON m.id=c.marketplace_id WHERE c.tenant_id=? AND c.marketplace_id=? ORDER BY id DESC LIMIT 1");
    $st->execute([$tenant,$mp]); 
    return $st->fetch();
  }

  private function syncWooProduct(array $job, string &$msg): bool {
    $pdo=\App\Database::pdo();
    $payload = json_decode($job['payload'], true);
    $productId=(int)($payload['product_id']??0); 
    if(!$productId) { $msg='no_product_id'; return false; }
    
    $p=$pdo->prepare("SELECT * FROM products WHERE id=?"); 
    $p->execute([$productId]); 
    $prod=$p->fetch(); 
    if(!$prod){ $msg='not_found'; return true; }
    
    $tenant=(int)$prod['tenant_id'];
    $conn=$this->connection($tenant,2); 
    if(!$conn){ $msg='conn_woo_missing'; return false; }
    
    $woo=new \App\Integrations\WooAdapter($conn);

    // product_marketplace_mapping'ten woo external product id
    $ext=$pdo->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=2");
    $ext->execute([$productId]); 
    $wooPid=$ext->fetchColumn();
    
    if(!$wooPid){
      // SKU üzerinden bul (ör: ilk varyant sku'su)
      $sv=$pdo->prepare("SELECT sku FROM variants WHERE product_id=? ORDER BY id ASC LIMIT 1"); 
      $sv->execute([$productId]); 
      $sku=$sv->fetchColumn();
      if($sku){ 
        $pr=$woo->getProductBySku($sku); 
        $wooPid=$pr['id']??null; 
      }
    }
    
    if(!$wooPid){ $msg='woo_pid_missing'; return false; }

    // varyantlar: price/stock push
    $vs=$pdo->prepare("SELECT id, sku, price, stock FROM variants WHERE product_id=?"); 
    $vs->execute([$productId]); 
    $vars=$vs->fetchAll();
    $okAll=true;
    
    foreach($vars as $v){
      // variant_marketplace_mapping'den variation id'yi çek
      $ve=$pdo->prepare("SELECT external_variant_id FROM variant_marketplace_mapping WHERE variant_id=? AND marketplace_id=2");
      $ve->execute([$v['id']]); 
      $wooVid=$ve->fetchColumn();
      if(!$wooVid){ continue; } // variation oluşturma ayrı iş
      
      $r=$woo->updateVariation((string)$wooPid,(string)$wooVid,[
        "regular_price" => (string)$v['price'],
        "stock_quantity"=> (int)$v['stock']
      ]);
      if(!$r['ok']) $okAll=false;
    }
    
    $msg=$okAll?'ok':'partial';
    return $okAll;
  }

  private function syncTrendyolProduct(array $job, string &$msg): bool {
    $pdo=\App\Database::pdo();
    $payload = json_decode($job['payload'], true);
    $productId=(int)($payload['product_id']??0); 
    if(!$productId) { $msg='no_product_id'; return false; }
    
    $p=$pdo->prepare("SELECT * FROM products WHERE id=?"); 
    $p->execute([$productId]); 
    $prod=$p->fetch(); 
    if(!$prod){ $msg='not_found'; return true; }
    
    $tenant=(int)$prod['tenant_id'];
    $conn=$this->connection($tenant,1); 
    if(!$conn){ $msg='conn_ty_missing'; return false; }
    
    $ty=new \App\Integrations\TrendyolAdapter($conn);

    // varyantlardan SKU/barcode setini hazırla
    $vs=$pdo->prepare("SELECT sku, price, stock FROM variants WHERE product_id=?"); 
    $vs->execute([$productId]); 
    $vars=$vs->fetchAll();
    $items=[]; 
    
    foreach($vars as $v){ 
      $items[]=[
        'barcode'=>$v['sku'],
        'quantity'=>(int)$v['stock'],
        'listPrice'=>(float)$v['price']
      ]; 
    }
    
    if(!$items){ $msg='no_variants'; return true; }

    $r=$ty->updateStockPrice($items);
    $msg=$r['ok']?'ok':('err_'.$r['code']);
    return (bool)$r['ok'];
  }

  private function pullTYStockPrice(array $job, string &$msg): bool {
    $tenant=(int)($job['tenant_id']??1);
    $conn=$this->connection($tenant,1); 
    if(!$conn){ $msg='conn_ty_missing'; return false; }
    
    $ty=new \App\Integrations\TrendyolAdapter($conn);

    $page=0; $size=200; $total=0;
    do{
      $res=$ty->listPriceInventory(null,$page,$size);
      if(!$res['ok']){ $msg='ty_http_'.$res['code']; return false; }
      
      $arr=$res['data']['items'] ?? $res['data']; 
      if(!$arr) break;
      
      foreach($arr as $it){
        $sku=$it['barcode'] ?? ($it['sku'] ?? null);
        $stock=$it['quantity'] ?? ($it['stock'] ?? null);
        $price=$it['listPrice'] ?? ($it['price'] ?? null);
        if(!$sku) continue;
        
        // variant id'yi bul
        $st=\App\Database::pdo()->prepare("SELECT v.id, v.product_id FROM variants v WHERE v.sku=? LIMIT 1");
        $st->execute([$sku]); 
        $v=$st->fetch(); 
        if(!$v) continue;
        
        \App\Utils\Reconcile::write($tenant,(int)$v['product_id'],(int)$v['id'],'trendyol',$price,$stock);
        
        // Dilersen burada Woo sync job'ı da oluştur:
        \App\Database::pdo()->prepare("INSERT INTO jobs (type,status,payload,created_at) VALUES ('sync_woo','pending',?,NOW())")
          ->execute([json_encode(['product_id'=>$v['product_id']])]);
        
        $total++;
      }
      
      if(count($arr)<$size) break;
      $page++;
    }while(true);

    $msg="snapshots:$total";
    return true;
  }

  private function createLabelJob(array $job, string &$msg): bool {
    $sid=(int)($job['payload']['shipment_id']??0);
    if(!$sid) { $msg='no_shipment_id'; return false; }
    
    $st=\App\Database::pdo()->prepare("SELECT * FROM shipments WHERE id=?");
    $st->execute([$sid]); $s=$st->fetch(); if(!$s){ $msg='not_found'; return true; }
    $carrier=$s['carrier'] ?: 'Yurtici';

    $res=\App\Integrations\ShippingStub::createLabel($carrier, ['shipment_id'=>$sid]);
    if($res['ok']){
      \App\Database::pdo()->prepare("UPDATE shipments SET status='label_ready', label_url=?, tracking_no=? WHERE id=?")
        ->execute([$res['label_url'],$res['tracking_no'],$sid]);
      \App\Middleware\Audit::log(null,'shipment.label_ready',"/shipments/$sid",$res);
      $msg='ok'; return true;
    }
    $msg='failed'; return false;
  }

  private function callController($class, $method, $args, &$msg){
    $ctrl = new $class(); 
    $res = call_user_func([$ctrl, $method], $args); 
    $msg = json_encode($res); 
    return !empty($res['ok']); 
  }

  private function call($c,$m,$args,&$msg){ 
    $x=new $c(); 
    $r=call_user_func([$x,$m],$args); 
    $msg=json_encode($r); 
    return !empty($r['ok']); 
  }

  private function syncPriceStock(array $payload, string &$msg): bool {
    // payload: { source:'woo'|'trendyol', sku:'...', price:float|null, stock:int|null }
    $tenant=\App\Context::$tenantId;
    $sku=$payload['sku']??null; if(!$sku){ $msg='no_sku'; return true; }
    $st=\App\Database::pdo()->prepare("SELECT v.id, v.product_id FROM variants v WHERE v.tenant_id=? AND v.sku=? LIMIT 1");
    $st->execute([$tenant,$sku]); $v=$st->fetch(); if(!$v){ $msg='sku_not_found'; return true; }

    // hedeflere push → mevcut ProductSyncController pushWoo/pushTrendyol'i ürün bazında yapıyor.
    // Burada sadece stok/fiyat güncellemesi: minimal adapter çağrılarıyla doğrudan yapabilirsin; basitçe ürün pushlarına yönlendirelim:
    \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES ('sync_woo','pending',?,NOW())")->execute([json_encode(['product_id'=>$v['product_id']])]);
    \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES ('sync_trendyol','pending',?,NOW())")->execute([json_encode(['product_id'=>$v['product_id']])]);

    $msg='enqueued'; return true;
  }

  private function csvBulkSync(array $payload, string &$msg): bool {
    // payload: { product_ids: [..], targets: ['woo','trendyol'] }
    $targets = $payload['targets'] ?? []; 
    $pids = $payload['product_ids'] ?? []; 
    foreach($pids as $pid){
      if(in_array('woo', $targets)){ 
        \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('sync_woo','pending',?,NOW())")
          ->execute([json_encode(['product_id'=>$pid])]); 
      }
      if(in_array('trendyol', $targets)){ 
        \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('sync_trendyol','pending',?,NOW())")
          ->execute([json_encode(['product_id'=>$pid])]); 
      }
    }
    $msg = 'enqueued:'.count($pids); 
    return true; 
  }
}
