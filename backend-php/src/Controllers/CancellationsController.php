<?php
namespace App\Controllers;
use App\Database;

final class CancellationsController {
  private function conn(){ 
    $st=Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_name='trendyol' AND status='active' ORDER BY id DESC LIMIT 1"); 
    $st->execute([\App\Context::$tenantId]); 
    return $st->fetch(); 
  }

  public function pull(): array {
    $end=new \DateTime('now'); 
    $start=(clone $end)->modify('-7 days');
    $ty=new \App\Integrations\TrendyolAdapter($this->conn());
    $page=0; $size=200; $ins=0;
    do{
      $res=$ty->listCancellations($start->format(DATE_ATOM),$end->format(DATE_ATOM),$page,$size);
      if(!$res['ok']) return ['ok'=>false,'error'=>'ty_http_'.$res['code']];
      $items=$res['data']['items'] ?? $res['data'] ?? [];
      if(!$items) break;
      foreach($items as $c){
        $id=(string)($c['id']??$c['cancellationId']??''); 
        $ord=(string)($c['orderNumber']??''); 
        $reason=$c['reason'] ?? ''; 
        $status=strtolower($c['status'] ?? 'requested');
        $reqAt=!empty($c['requestDate'])?(new \DateTime($c['requestDate']))->format('Y-m-d H:i:s'):null;
        Database::pdo()->prepare("INSERT INTO mp_cancellations(tenant_id,mp,external_id,order_external_id,reason,status,requested_at,payload)
          VALUES (?,?,?,?,?,?,?,?)
          ON DUPLICATE KEY UPDATE status=VALUES(status), reason=VALUES(reason), payload=VALUES(payload)")
          ->execute([\App\Context::$tenantId,'trendyol',$id,$ord,$reason,$status,$reqAt,json_encode($c,JSON_UNESCAPED_UNICODE)]);
        $ins++;
      }
      if(count($items)<$size) break; $page++;
    }while(true);
    return ['ok'=>true,'imported'=>$ins];
  }

  public function approve(array $p,array $b): array {
    $ext=$p[0]??''; 
    $note=$b['note']??null;
    $ty=new \App\Integrations\TrendyolAdapter($this->conn());
    $res=$ty->approveCancel($ext,$note);
    if($res['ok']){
      Database::pdo()->prepare("UPDATE mp_cancellations SET status='approved', resolved_at=NOW() WHERE tenant_id=? AND external_id=?")
        ->execute([\App\Context::$tenantId,$ext]);
      \App\Middleware\Audit::log(null,'cancel.approve',"/cancellations/$ext",['note'=>$note]);
    }
    return ['ok'=>$res['ok'],'code'=>$res['code']??200];
  }

  public function pushToWoo(array $p): array {
    $ext=$p[0]??'';
    $st=\App\Database::pdo()->prepare("SELECT * FROM mp_cancellations WHERE tenant_id=? AND external_id=?");
    $st->execute([\App\Context::$tenantId,$ext]); $can=$st->fetch(); if(!$can) return ['ok'=>false,'error'=>'cancel_not_found'];

    $cw=\App\Database::pdo()->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_name='woocommerce' AND status='active' ORDER BY id DESC LIMIT 1");
    $cw->execute([\App\Context::$tenantId]); $conn=$cw->fetch(); if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];
    $woo=new \App\Integrations\WooAdapter($conn);

    $ord=\App\Database::pdo()->prepare("SELECT * FROM orders WHERE tenant_id=? AND mp='trendyol' AND external_id=? LIMIT 1");
    $ord->execute([\App\Context::$tenantId,$can['order_external_id']]); $o=$ord->fetch(); if(!$o) return ['ok'=>false,'error'=>'local_order_missing'];

    $wooOrderId=$o['external_id'];
    $res=$woo->updateOrderStatus((string)$wooOrderId,'cancelled');
    if($res['ok']){
      \App\Middleware\Audit::log(null,'woo.cancel_order',"/cancellations/$ext",['woo_order'=>$wooOrderId]);
    }
    return ['ok'=>$res['ok'],'code'=>$res['code']??200,'data'=>$res['data']??null];
  }
}
