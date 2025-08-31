<?php
namespace App\Controllers;
use App\Database;

final class OrderImportController {
  private function tenant(){ return (int)\App\Context::$tenantId; }
  private function conn(int $mp){
    $st=Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_id=? ORDER BY id DESC LIMIT 1");
    $st->execute([$this->tenant(),$mp]); return $st->fetch();
  }

  public function pullWoo(array $p, array $b, array $q): array {
    $c=$this->conn(2); if(!$c) return ['ok'=>false,'error'=>'conn_woo_missing'];
    $page=(int)($q['page']??1); $per=(int)($q['per']??50); $since=$q['since']??null;
    $woo=new \App\Integrations\WooAdapter($c);
    $res=$woo->listOrders($page,$per,$since); if(!$res['ok']) return ['ok'=>false,'error'=>'woo_http_'.$res['code']];
    $n=$this->upsertWoo($res['data']); return ['ok'=>true,'imported'=>$n,'page'=>$page];
  }

  public function pullTrendyol(array $p, array $b, array $q): array {
    $c=$this->conn(1); if(!$c) return ['ok'=>false,'error'=>'conn_ty_missing'];
    $page=(int)($q['page']??0); $size=(int)($q['size']??200); $since=$q['since']??null;
    $ty=new \App\Integrations\TrendyolAdapter($c);
    $res=$ty->listOrdersSimple($page,$size,$since); if(!$res['ok']) return ['ok'=>false,'error'=>'ty_http_'.$res['code']];
    $items=$res['data']['content'] ?? $res['data']['orders'] ?? $res['data'] ?? [];
    $n=$this->upsertTrendyol($items); return ['ok'=>true,'imported'=>$n,'page'=>$page];
  }

  private function upsertWoo(array $orders): int {
    $pdo=Database::pdo(); $t=$this->tenant(); $n=0;
    foreach($orders as $o){
      $oid=(string)($o['id']??''); if($oid==='') continue;
      $st=$pdo->prepare("SELECT id FROM orders WHERE tenant_id=? AND origin_mp='woo' AND origin_external_id=?");
      $st->execute([$t,$oid]); $local=(int)($st->fetchColumn()?:0);
      $cust=$o['billing']['first_name'].' '.$o['billing']['last_name'];
      $email=$o['billing']['email']??null;
      $status=$o['status']??'pending';
      if(!$local){
        $pdo->prepare("INSERT INTO orders(tenant_id,origin_mp,origin_external_id,customer_name,customer_email,total_amount,currency,status,shipping_address,billing_address,created_at,updated_at)
          VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())")
          ->execute([$t,'woo',$oid,$cust,$email,(float)($o['total']??0),($o['currency']??'TRY'),$status,json_encode($o['shipping']),json_encode($o['billing'])]);
        $local=(int)$pdo->lastInsertId();
      }else{
        $pdo->prepare("UPDATE orders SET customer_name=?, customer_email=?, total_amount=?, currency=?, status=?, shipping_address=?, billing_address=?, updated_at=NOW() WHERE id=?")
          ->execute([$cust,$email,(float)($o['total']??0),($o['currency']??'TRY'),$status,json_encode($o['shipping']),json_encode($o['billing']),$local]);
      }
      // items
      $pdo->prepare("DELETE FROM order_items WHERE order_id=?")->execute([$local]);
      foreach($o['line_items']??[] as $li){
        $sku=$li['sku']??null;
        $vid=null; if($sku){ $q=$pdo->prepare("SELECT id FROM variants WHERE tenant_id=? AND sku=? LIMIT 1"); $q->execute([$t,$sku]); $vid=$q->fetchColumn()?:null; }
        $pdo->prepare("INSERT INTO order_items(order_id,variant_id,sku,name,quantity,price,attrs_json) VALUES (?,?,?,?,?,?,?)")
          ->execute([$local,$vid,$sku,$li['name']??'',(int)($li['quantity']??1),(float)($li['price']??0),json_encode([])]);
      }
      // mapping
      $pdo->prepare("INSERT IGNORE INTO order_marketplace_mapping(order_id,marketplace_id,external_id) VALUES (?,?,?)")
        ->execute([$local,2,$oid]);
      $pdo->prepare("INSERT INTO order_status_history(order_id,status) VALUES (?,?)")->execute([$local,$status]);
      $n++;
    }
    return $n;
  }

  private function upsertTrendyol(array $orders): int {
    $pdo=Database::pdo(); $t=$this->tenant(); $n=0;
    foreach($orders as $o){
      $oid=(string)($o['orderNumber'] ?? $o['id'] ?? ''); if($oid==='') continue;
      $st=$pdo->prepare("SELECT id FROM orders WHERE tenant_id=? AND origin_mp='trendyol' AND origin_external_id=?");
      $st->execute([$t,$oid]); $local=(int)($st->fetchColumn()?:0);
      $cust=$o['customer']["fullName"] ?? ($o['shipmentAddress']['fullName'] ?? '');
      $email=$o['customer']['email'] ?? null;
      $status=strtolower($o['status'] ?? 'pending');
      if(!$local){
        $pdo->prepare("INSERT INTO orders(tenant_id,origin_mp,origin_external_id,customer_name,customer_email,total_amount,currency,status,shipping_address,billing_address,created_at,updated_at)
          VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())")
          ->execute([$t,'trendyol',$oid,$cust,$email,(float)($o['totalPrice']??0), 'TRY', $status, json_encode($o['shipmentAddress']??[]), json_encode($o['billingAddress']??[]) ]);
        $local=(int)$pdo->lastInsertId();
      }else{
        $pdo->prepare("UPDATE orders SET customer_name=?, customer_email=?, total_amount=?, status=?, shipping_address=?, billing_address=?, updated_at=NOW() WHERE id=?")
          ->execute([$cust,$email,(float)($o['totalPrice']??0),$status,json_encode($o['shipmentAddress']??[]),json_encode($o['billingAddress']??[]),$local]);
      }
      // items
      $pdo->prepare("DELETE FROM order_items WHERE order_id=?")->execute([$local]);
      foreach($o['lines']??($o['items']??[]) as $li){
        $sku=$li['barcode'] ?? $li['sku'] ?? null;
        $vid=null; if($sku){ $q=$pdo->prepare("SELECT id FROM variants WHERE tenant_id=? AND sku=? LIMIT 1"); $q->execute([$t,$sku]); $vid=$q->fetchColumn()?:null; }
        $name=$li['productName'] ?? $li['name'] ?? '';
        $qty=(int)($li['quantity'] ?? $li['amount'] ?? 1);
        $price=(float)($li['price'] ?? $li['unitPrice'] ?? 0);
        $pdo->prepare("INSERT INTO order_items(order_id,variant_id,sku,name,quantity,price,attrs_json) VALUES (?,?,?,?,?,?,?)")
          ->execute([$local,$vid,$sku,$name,$qty,$price,json_encode([])]);
      }
      // mapping
      $pdo->prepare("INSERT IGNORE INTO order_marketplace_mapping(order_id,marketplace_id,external_id) VALUES (?,?,?)")
        ->execute([$local,1,$oid]);
      $pdo->prepare("INSERT INTO order_status_history(order_id,status) VALUES (?,?)")->execute([$local,$status]);
      $n++;
    }
    return $n;
  }
}
