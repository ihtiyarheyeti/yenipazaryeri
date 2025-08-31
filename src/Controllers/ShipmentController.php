<?php
namespace App\Controllers;
use App\Database;

final class ShipmentController {
  public function create(array $p,array $b): array {
    // payload: order_id | order_external_id, carrier, (opsiyonel) create_label:true
    $orderId=$b['order_id']??null; 
    $ext=$b['order_external_id']??null; 
    $carrier=$b['carrier']??'Manual';
    Database::pdo()->prepare("INSERT INTO shipments(tenant_id,mp,order_id,order_external_id,carrier,status) VALUES(?,?,?,?,?,?)")
      ->execute([\App\Context::$tenantId,'internal',$orderId,$ext,$carrier,'created']);
    $id=(int)Database::pdo()->lastInsertId();
    \App\Middleware\Audit::log(null,'shipment.create',"/shipments/$id",['carrier'=>$carrier]);
    // Eğer entegrasyon varsa burada queue: create_label
    \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('create_label','pending',?,NOW())")
      ->execute([json_encode(['shipment_id'=>$id])]);
    return ['ok'=>true,'id'=>$id];
  }

  public function updateTracking(array $p,array $b): array {
    $id=(int)$p[0]; 
    $track=$b['tracking_no']??''; 
    $label=$b['label_url']??null; 
    $status=$b['status']??'label_ready';
    Database::pdo()->prepare("UPDATE shipments SET tracking_no=?, label_url=?, status=? WHERE id=?")
      ->execute([$track,$label,$status,$id]);
    \App\Middleware\Audit::log(null,'shipment.update',"/shipments/$id",['status'=>$status]);
    return ['ok'=>true];
  }
}
