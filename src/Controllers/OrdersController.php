<?php
namespace App\Controllers;
use App\Database;
use App\Context;

final class OrdersController {
  public function list(array $p,array $b,array $q): array {
    $tenant=(int)(Context::$tenantId);
    
    // Yeni orders_new tablosunu kullan
    try {
      $st=Database::pdo()->prepare("SELECT * FROM orders_new WHERE tenant_id=? ORDER BY created_at_mp DESC");
      $st->execute([$tenant]);
      return ['ok'=>true,'items'=>$st->fetchAll()];
    } catch (\PDOException $e) {
      // Hata detayını logla
      error_log("Orders table error: " . $e->getMessage());
      return ['ok'=>true,'items'=>[],'note'=>'orders table error: ' . $e->getMessage()];
    }
  }

  public function pullTrendyol(array $p,array $b,array $q): array {
    $tenant=(int)(Context::$tenantId);
    // tarih aralığı: son 30 gün default (daha önceki siparişleri de çek)
    $end = new \DateTime('now'); $start = (clone $end)->modify('-30 days');
    if(!empty($q['start'])) $start = new \DateTime($q['start']);
    if(!empty($q['end']))   $end   = new \DateTime($q['end']);

    // connection - mevcut tablo yapısını kullan
    $st=Database::pdo()->prepare("SELECT c.*, c.marketplace_name as name FROM marketplace_connections c WHERE c.tenant_id=? AND c.marketplace_name='trendyol' AND c.status='active' ORDER BY id DESC LIMIT 1");
    $st->execute([$tenant]); $conn=$st->fetch(); if(!$conn) return ['ok'=>false,'error'=>'conn_trendyol_missing'];

    $ty = new \App\Integrations\TrendyolAdapter($conn);
    $page=0; $size=200; $imported=0;
    do{
      $res=$ty->listOrders($start->format(DATE_ATOM), $end->format(DATE_ATOM), $page, $size);
      if(!$res['ok']) return ['ok'=>false,'error'=>'ty_http_'.$res['code']];
      $items=$res['data']['content'] ?? $res['data']['items'] ?? $res['data'] ?? [];
      if(!$items) break;

      foreach($items as $o){
        $oid   = (string)($o['id'] ?? $o['orderNumber'] ?? '');
        $stat  = (string)($o['status'] ?? '');
        $cur   = (string)($o['currency'] ?? 'TRY');
        $total = (float)($o['totalPrice'] ?? $o['total'] ?? 0);
        $cust  = trim(($o['customerFirstName'] ?? '').' '.($o['customerLastName'] ?? ''));
        $mail  = $o['customerEmail'] ?? null; $phone=$o['customerPhone'] ?? null;
        $ship  = json_encode($o['shippingAddress'] ?? ($o['address'] ?? []), JSON_UNESCAPED_UNICODE);
        $bill  = json_encode($o['billingAddress']  ?? [], JSON_UNESCAPED_UNICODE);
        $created = !empty($o['createdDate']) ? (new \DateTime($o['createdDate']))->format('Y-m-d H:i:s') : null;

        Database::pdo()->prepare("INSERT INTO orders_new (tenant_id, mp, external_id, status, currency, total, customer_name, customer_email, phone, shipping_address, billing_address, created_at_mp)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
          ON DUPLICATE KEY UPDATE status=VALUES(status), total=VALUES(total), currency=VALUES(currency), updated_at=NOW()")
          ->execute([$tenant,'trendyol',$oid,$stat,$cur,$total,$cust,$mail,$phone,$ship,$bill,$created]);

        $localOrderId = (int)Database::pdo()->lastInsertId();
        if($localOrderId===0){
          // zaten vardı → id'yi çek
          $g=Database::pdo()->prepare("SELECT id FROM orders_new WHERE tenant_id=? AND mp='trendyol' AND external_id=?");
          $g->execute([$tenant,$oid]); $localOrderId=(int)$g->fetchColumn();
        }

        // items
        $lines = $o['lines'] ?? $o['items'] ?? [];
        if($lines){
          foreach($lines as $it){
            $sku = (string)($it['barcode'] ?? $it['sku'] ?? '');
            $qty = (int)($it['quantity'] ?? 1);
            $price=(float)($it['price'] ?? $it['unitPrice'] ?? 0);
            $totalL=(float)($it['total'] ?? ($qty*$price));
            // local variant eşle
            $vst=Database::pdo()->prepare("SELECT v.id, v.product_id FROM variants v WHERE v.sku=? LIMIT 1");
            $vst->execute([$sku]); $v=$vst->fetch();

            Database::pdo()->prepare("INSERT INTO order_items (order_id, product_id, variant_id, sku, name, qty, price, total)
              VALUES (?,?,?,?,?,?,?,?)")
              ->execute([$localOrderId, $v['product_id']??null, $v['id']??null, $sku, ($it['name']??$sku), $qty, $price, $totalL]);
          }
        }
        $imported++;
      }

      if(count($items)<$size) break; $page++;
    }while(true);

    return ['ok'=>true,'imported'=>$imported];
  }

  public function pushToWoo(array $p,array $b,array $q): array {
    $tenant=(int)(Context::$tenantId);
    $oid=(int)($p[0]??0);
    $st=Database::pdo()->prepare("SELECT * FROM orders_new WHERE id=?");
    $st->execute([$oid]); $o=$st->fetch(); if(!$o) return ['ok'=>false,'error'=>'order_not_found'];
    // Woo connection - mevcut tablo yapısını kullan
    $cw=Database::pdo()->prepare("SELECT c.* FROM marketplace_connections c WHERE c.tenant_id=? AND c.marketplace_name='woocommerce' AND c.status='active' ORDER BY id DESC LIMIT 1");
    $cw->execute([$tenant]); $conn=$cw->fetch(); if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];
    $woo=new \App\Integrations\WooAdapter($conn);

    // billing/shipping
    $bill = $o['billing_address']? json_decode($o['billing_address'],true):[];
    $ship = $o['shipping_address']? json_decode($o['shipping_address'],true):$bill;

    // satırlar
    $it=Database::pdo()->prepare("SELECT * FROM order_items WHERE order_id=?");
    $it->execute([$oid]); $rows=$it->fetchAll();
    $lineItems=[];
    foreach($rows as $r){
      // Woo'da product_id bulmaya çalış (mapping tablosu varsa oradan; yoksa SKU ile product GET)
      $pid=null;
      if($r['variant_id']){
        $m=Database::pdo()->prepare("SELECT external_id FROM product_marketplace_mapping WHERE product_id=? AND marketplace_id=2 LIMIT 1");
        $m->execute([$r['product_id']]); $pid=$m->fetchColumn();
      }
      if(!$pid && $r['sku']){
        $wp=$woo->getProductBySku($r['sku']); $pid=$wp['id'] ?? null;
      }
      $lineItems[]=[
        $pid? 'product_id':'sku' => $pid ?: $r['sku'],
        'quantity'=> (int)$r['qty'],
        'price'   => (string)($r['price'] ?? 0)
      ];
    }

    $payload=[
      'payment_method' => 'bacs',
      'payment_method_title' => 'Marketplace',
      'set_paid' => true,
      'billing' => [
        'first_name'=>$bill['firstName']??($bill['first_name']??($o['customer_name']??'')),
        'last_name' =>$bill['lastName']??($bill['last_name']??''),
        'email'     =>$o['customer_email']??'',
        'phone'     =>$o['phone']??'',
        'address_1' =>$bill['address1']??($bill['address']??''),
        'city'      =>$bill['city']??'',
        'country'   =>$bill['country']??'TR',
      ],
      'shipping' => [
        'first_name'=>$ship['firstName']??'',
        'last_name' =>$ship['lastName']??'',
        'address_1' =>$ship['address1']??($ship['address']??''),
        'city'      =>$ship['city']??'',
        'country'   =>$ship['country']??'TR',
      ],
      'line_items'=>$lineItems
    ];

    $res=$woo->createOrder($payload);
    return ['ok'=>$res['ok'],'code'=>$res['code'],'order'=>$res['data']];
  }
}
