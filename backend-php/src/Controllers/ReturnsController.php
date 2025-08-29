<?php
namespace App\Controllers;
use App\Database;
use App\Context;

final class ReturnsController {
  private function conn(){ 
    $st=Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_name='trendyol' AND status='active' ORDER BY id DESC LIMIT 1"); 
    $st->execute([Context::$tenantId]); 
    return $st->fetch(); 
  }

  public function pull(array $p,array $b,array $q): array {
    $end=new \DateTime('now'); 
    $start=(clone $end)->modify('-7 days');
    $ty=new \App\Integrations\TrendyolAdapter($this->conn());
    $page=0; $size=200; $ins=0;
    do{
      $res=$ty->listReturns($start->format(DATE_ATOM),$end->format(DATE_ATOM),$page,$size);
      if(!$res['ok']) return ['ok'=>false,'error'=>'ty_http_'.$res['code']];
      $items=$res['data']['items'] ?? $res['data'] ?? [];
      if(!$items) break;
      foreach($items as $r){
        $id=(string)($r['id']??$r['returnId']??'');
        $ord=(string)($r['orderNumber']??$r['orderId']??'');
        $reason=$r['reason'] ?? '';
        $status=strtolower($r['status'] ?? 'requested');
        $reqAt=!empty($r['requestDate'])?(new \DateTime($r['requestDate']))->format('Y-m-d H:i:s'):null;
        Database::pdo()->prepare("INSERT INTO mp_returns(tenant_id,mp,external_id,order_external_id,reason,status,requested_at,payload)
          VALUES (?,?,?,?,?,?,?,?)
          ON DUPLICATE KEY UPDATE status=VALUES(status), reason=VALUES(reason), payload=VALUES(payload)")
          ->execute([Context::$tenantId,'trendyol',$id,$ord,$reason,$status,$reqAt,json_encode($r,JSON_UNESCAPED_UNICODE)]);
        $ins++;
      }
      if(count($items)<$size) break; $page++;
    }while(true);
    return ['ok'=>true,'imported'=>$ins];
  }

  public function act(array $p,array $b): array {
    $ext=$p[0]??''; 
    $action=$b['action']??'accept'; 
    $note=$b['note']??null;
    $ty=new \App\Integrations\TrendyolAdapter($this->conn());
    $res=$ty->actOnReturn($ext,$action,$note);
    if($res['ok']){
      Database::pdo()->prepare("UPDATE mp_returns SET status=?, resolved_at=NOW() WHERE tenant_id=? AND mp='trendyol' AND external_id=?")
        ->execute([$action==='accept'?'accepted':'rejected', \App\Context::$tenantId, $ext]);
      \App\Middleware\Audit::log(null,'return.'.$action,"/returns/$ext",['note'=>$note]);
    }
    return ['ok'=>$res['ok'],'code'=>$res['code']??200];
  }

  public function pushToWoo(array $p): array {
    $ext=$p[0]??''; // Trendyol return external_id
    // Return kaydını ve ilişkili orderı bul
    $st=\App\Database::pdo()->prepare("SELECT * FROM mp_returns WHERE tenant_id=? AND external_id=?");
    $st->execute([\App\Context::$tenantId,$ext]); $ret=$st->fetch(); if(!$ret) return ['ok'=>false,'error'=>'return_not_found'];

    // Woo bağlantısı
    $cw=\App\Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_name='woocommerce' AND status='active' ORDER BY id DESC LIMIT 1");
    $cw->execute([\App\Context::$tenantId]); $conn=$cw->fetch(); if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];
    $woo=new \App\Integrations\WooAdapter($conn);

    // Bizdeki siparişi bul (opsiyonel); yoksa Woo order id'yi dıştan bilmen gerekebilir
    $ord=\App\Database::pdo()->prepare("SELECT * FROM orders WHERE tenant_id=? AND mp='trendyol' AND external_id=? LIMIT 1");
    $ord->execute([\App\Context::$tenantId,$ret['order_external_id']]); $o=$ord->fetch();
    if(!$o) return ['ok'=>false,'error'=>'local_order_missing'];

    // Woo order id'sini mapping'ten/sku'dan üretmek zor olabilir; demo: order external_id = woo order id kabul et (gerekirse map tablosu eklersin)
    $wooOrderId=$o['external_id']; // gerçek hayatta mapping tutmak daha iyidir.

    // Basit tüm tutarı iade et (kalem kalem istersen order_items'ten çekebilirsin)
    $amount = (string)($o['total'] ?? '0');
    $payload=['amount'=>$amount, 'reason'=>'Trendyol return '.$ext, 'refund_payment'=>true];
    $res=$woo->createRefund((string)$wooOrderId, $payload);
    if($res['ok']){
      \App\Middleware\Audit::log(null,'woo.refund',"/returns/$ext",['woo_order'=>$wooOrderId,'amount'=>$amount]);
    }
    return ['ok'=>$res['ok'],'code'=>$res['code']??200,'data'=>$res['data']??null];
  }
}
