<?php
namespace App\Controllers;
use App\Database;
use App\Context;

final class CatalogController {
  // Pazaryerinden kategori çek (adapter'dan getirip cache'e yaz)
  public function pullCategories(array $p, array $b, array $q): array {
    $mp=(int)($q['marketplace_id']??0); 
    $tenant=(int)($q['tenant_id']??Context::$tenantId);
    if(!$mp) return ['ok'=>false,'error'=>'marketplace_id'];
    
    // adapter çağır (Woo: categories list; Trendyol: taxonomy)
    $cats = \App\Integrations\CatalogAdapter::fetch($mp, $tenant);
    $pdo=Database::pdo();
    $ins=0;
    foreach($cats as $c){
      $pdo->prepare("INSERT INTO marketplace_categories (marketplace_id, external_id, parent_external_id, name, path)
                     VALUES (?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE name=VALUES(name), path=VALUES(path), parent_external_id=VALUES(parent_external_id)")
          ->execute([$mp,$c['id'],$c['parent']??null,$c['name'],$c['path']??null]);
      $ins++;
    }
    return ['ok'=>true,'count'=>$ins];
  }

  // Eşleme CRUD (category_mapping & attribute_mapping)
  public function listCategoryMap(array $p, array $b, array $q): array {
    $tenant=(int)($q['tenant_id']??Context::$tenantId); 
    $mp=(int)($q['marketplace_id']??0);
    $st=Database::pdo()->prepare("SELECT * FROM category_mapping WHERE tenant_id=? AND marketplace_id=? ORDER BY local_path");
    $st->execute([$tenant,$mp]); 
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }

  public function upsertCategoryMap(array $p,array $b): array {
    $tenant=(int)($b['tenant_id']??Context::$tenantId);
    $mp=(int)($b['marketplace_id']??0);
    $local=$b['local_path']??''; 
    $ext=$b['external_id']??'';
    if(!$mp||!$local||!$ext) return ['ok'=>false,'error'=>'missing'];
    
    \App\Database::pdo()->prepare("INSERT INTO category_mapping (tenant_id,local_path,marketplace_id,external_id)
      VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE external_id=VALUES(external_id)")
      ->execute([$tenant,$local,$mp,$ext]);
    return ['ok'=>true];
  }

  public function listAttrMap(array $p, array $b, array $q): array {
    $tenant=(int)($q['tenant_id']??Context::$tenantId); 
    $mp=(int)($q['marketplace_id']??0);
    $st=\App\Database::pdo()->prepare("SELECT * FROM attribute_mapping WHERE tenant_id=? AND marketplace_id=? ORDER BY local_key");
    $st->execute([$tenant,$mp]); 
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }

  public function upsertAttrMap(array $p,array $b): array {
    $tenant=(int)($b['tenant_id']??Context::$tenantId);
    $mp=(int)($b['marketplace_id']??0);
    $lk=$b['local_key']??''; 
    $ek=$b['external_key']??''; 
    $vm=$b['value_map']??null;
    if(!$mp||!$lk||!$ek) return ['ok'=>false,'error'=>'missing'];
    
    \App\Database::pdo()->prepare("INSERT INTO attribute_mapping (tenant_id,local_key,marketplace_id,external_key,value_map)
      VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE external_key=VALUES(external_key), value_map=VALUES(value_map)")
      ->execute([$tenant,$lk,$mp,$ek, $vm?json_encode($vm):null]);
    return ['ok'=>true];
  }
}
