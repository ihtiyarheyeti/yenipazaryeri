<?php
namespace App\Controllers;
use App\Database;

final class OrderSyncController {
  private function tenant(){ return (int)\App\Context::$tenantId; }
  
  private function conn(int $mp){
    $st=Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_id=? ORDER BY id DESC LIMIT 1");
    $st->execute([$this->tenant(),$mp]); return $st->fetch();
  }

  private function prodBySku(string $sku){
    $st=\App\Database::pdo()->prepare("SELECT v.id as variant_id, v.product_id, p.name FROM variants v JOIN products p ON p.id=v.product_id WHERE v.tenant_id=? AND v.sku=? LIMIT 1");
    $st->execute([$this->tenant(),$sku]); return $st->fetch();
  }

  private function mapWooLine(string $sku, float $price, int $qty): array {
    // Woo kalemi: sku → product_id/variation_id çözmeye çalış (mapping varsa)
    $pdo=\App\Database::pdo();
    // product mapping
    $mp=$pdo->prepare("SELECT pm.external_id FROM variants v JOIN product_marketplace_mapping pm ON pm.product_id=v.product_id AND pm.marketplace_id=2 WHERE v.tenant_id=? AND v.sku=? LIMIT 1");
    $mp->execute([$this->tenant(),$sku]); $wooProductId=$mp->fetchColumn();

    $variationId=null;
    if($wooProductId){
      // variation mapping
      $vm=$pdo->prepare("SELECT external_variant_id FROM variant_marketplace_mapping vm JOIN variants v ON v.id=vm.variant_id WHERE v.tenant_id=? AND v.sku=? AND vm.marketplace_id=2 LIMIT 1");
      $vm->execute([$this->tenant(),$sku]); $variationId=$vm->fetchColumn()?:null;
    }

    $line=[
      'quantity'=>$qty,
      'subtotal'=> (string)($price*$qty),
      'total'   => (string)($price*$qty),
    ];
    if($variationId){
      $line['product_id']   = (int)$wooProductId;
      $line['variation_id'] = (int)$variationId;
    } elseif ($wooProductId){
      $line['product_id'] = (int)$wooProductId;
    } else {
      // Son çare: SKU ile direkt oluştur (Woo bazı temalarda kabul eder)
      $line['sku'] = $sku;
    }
    return $line;
  }

  public function pushToWoo(array $p): array {
    $oid=(int)$p[0]; $pdo=\App\Database::pdo(); $tenant=$this->tenant();
    // Kaynak trendyol olmalı (guard yoksa da izin ver)
    $o=$pdo->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$oid]); $ord=$o->fetch(); if(!$ord) return ['ok'=>false,'error'=>'order_not_found'];
    $items=$pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $items->execute([$oid]); $rows=$items->fetchAll();

    $pay=\App\Utils\Policy::get('payment_map',$tenant);
    $ship=\App\Utils\Policy::get('shipping_map',$tenant);
    $tax =\App\Utils\Policy::get('tax_policy',$tenant);

    $line_items=[];
    foreach($rows as $r){
      $line_items[] = $this->mapWooLine($r['sku']??'', (float)$r['price'], (int)$r['quantity']);
    }

    $payload=[
      'payment_method'      => $pay['woo_method']  ?? 'bacs',
      'payment_method_title'=> $pay['woo_title']   ?? 'Bank Transfer',
      'set_paid'            => false,
      'billing'             => json_decode($ord['billing_address'] ?: '[]', true) ?: ['first_name'=>$ord['customer_name']??'','email'=>$ord['customer_email']??''],
      'shipping'            => json_decode($ord['shipping_address']?: '[]', true) ?: [],
      'line_items'          => $line_items,
      'shipping_lines'      => [[ 'method_id'=>$ship['woo_method_id']??'flat_rate', 'method_title'=>$ship['woo_method_title']??'Flat Rate', 'total'=> '0.00' ]],
    ];

    $conn=$this->conn(2); if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];
    $woo=new \App\Integrations\WooAdapter($conn);
    $res=$woo->createOrder($payload);
    if(!$res['ok']) return $res;

    // mapping kaydı + status
    $wid=(string)($res['data']['id'] ?? '');
    if($wid!==''){
      $pdo->prepare("INSERT IGNORE INTO order_marketplace_mapping(order_id,marketplace_id,external_id) VALUES (?,?,?)")->execute([$oid,2,$wid]);
      $pdo->prepare("INSERT INTO order_status_history(order_id,status,note) VALUES (?,?,?)")->execute([$oid,'processing','created_in_woo']);
    }
    return ['ok'=>true,'data'=>$res['data']];
  }

  public function pushToTrendyol(array $p): array {
    $oid=(int)$p[0]; $pdo=\App\Database::pdo(); $tenant=$this->tenant();
    $o=$pdo->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$oid]); $ord=$o->fetch(); if(!$ord) return ['ok'=>false,'error'=>'order_not_found'];
    $items=$pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $items->execute([$oid]); $rows=$items->fetchAll();

    // Trendyol create order GERÇEK API değil → DEMO/SANDBOX: payload'ı kanonik üretelim
    $lines=[];
    foreach($rows as $r){
      $lines[]=[
        'barcode'   => $r['sku'],            // TY tarafında sku=barcode
        'quantity'  => (int)$r['quantity'],
        'price'     => (float)$r['price'],
        'attributes'=> json_decode($r['attrs_json']?:'[]',true) ?: []
      ];
    }
    $payload=[
      'customer'=>['fullName'=>$ord['customer_name'],'email'=>$ord['customer_email']],
      'shippingAddress'=> json_decode($ord['shipping_address']?:'[]',true) ?: [],
      'billingAddress' => json_decode($ord['billing_address'] ?: '[]',true) ?: [],
      'lines'=>$lines,
      'notes'=>'created from woo by yenipazaryeri',
      'tax_rate'=> (\App\Utils\Policy::get('tax_policy',$tenant)['default_rate'] ?? 20)
    ];

    $conn=$this->conn(1); if(!$conn) return ['ok'=>false,'error'=>'conn_ty_missing'];
    $ty=new \App\Integrations\TrendyolAdapter($conn);
    $res=$ty->createOrder($payload); // demo success
    if(!$res['ok']) return $res;

    $tid=(string)($res['data']['id'] ?? '');
    if($tid!==''){
      $pdo->prepare("INSERT IGNORE INTO order_marketplace_mapping(order_id,marketplace_id,external_id) VALUES (?,?,?)")->execute([$oid,1,$tid]);
      $pdo->prepare("INSERT INTO order_status_history(order_id,status,note) VALUES (?,?,?)")->execute([$oid,'processing','created_in_trendyol']);
    }
    return ['ok'=>true,'data'=>$res['data']];
  }

  public function updateWooStatus(array $p, array $b): array {
    $oid=(int)$p[0];
    $st=Database::pdo()->prepare("SELECT external_id FROM order_marketplace_mapping WHERE order_id=? AND marketplace_id=2");
    $st->execute([$oid]); $wooId=$st->fetchColumn(); if(!$wooId) return ['ok'=>false,'error'=>'woo_order_missing'];
    $conn=$this->conn(2); if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];
    $woo=new \App\Integrations\WooAdapter($conn);
    $res=$woo->updateOrderStatus((string)$wooId, $b['status']??'processing');
    if($res['ok']){
      \App\Database::pdo()->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?")->execute([$b['status'],$oid]);
      \App\Database::pdo()->prepare("INSERT INTO order_status_history(order_id,status,note) VALUES (?,?,?)")->execute([$oid,$b['status']]);
    }
    return $res;
  }
}
