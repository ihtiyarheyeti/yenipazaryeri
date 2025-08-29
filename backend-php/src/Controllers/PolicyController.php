<?php
namespace App\Controllers;
use App\Database;

final class PolicyController {
  public function list(): array {
    $st=Database::pdo()->prepare("SELECT id,key_name,value_json FROM policies WHERE tenant_id=? ORDER BY key_name");
    $st->execute([\App\Context::$tenantId]); return ['ok'=>true,'items'=>$st->fetchAll()];
  }
  
  public function upsert(array $p,array $b): array {
    $key=$b['key_name']??''; $val=$b['value_json']??null; if(!$key||$val===null) return ['ok'=>false,'error'=>'missing'];
    \App\Database::pdo()->prepare("INSERT INTO policies(tenant_id,key_name,value_json) VALUES (?,?,?)
      ON DUPLICATE KEY UPDATE value_json=VALUES(value_json)")
      ->execute([\App\Context::$tenantId,$key,json_encode($val)]);
    return ['ok'=>true];
  }
}
