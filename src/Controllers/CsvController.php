<?php
namespace App\Controllers;
use App\Database;

final class CsvController {

  // CategoryMappings Export
  public function exportCategoryMappings(array $p, array $b, array $q): void {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=category_mappings.csv");
    $out=fopen("php://output","w");
    fputcsv($out,["source_path","external_category_id","note"]);
    $st=Database::pdo()->prepare("SELECT source_path,external_category_id,note FROM category_mappings WHERE tenant_id=?");
    $st->execute([(int)($q['tenant_id']??0)]);
    while($row=$st->fetch(\PDO::FETCH_ASSOC)){ fputcsv($out,$row); }
    fclose($out);
    exit;
  }

  // CategoryMappings Import
  public function importCategoryMappings(array $p, array $b, array $q): array {
    if(empty($_FILES['file'])) return ['ok'=>false,'error'=>'no file'];
    $tenant=(int)($q['tenant_id']??0);
    $mp=(int)($q['marketplace_id']??0);
    $h=fopen($_FILES['file']['tmp_name'],"r");
    $pdo=Database::pdo();
    $st=$pdo->prepare("
      INSERT INTO category_mappings (tenant_id,marketplace_id,source_path,external_category_id,note,created_at,updated_at)
      VALUES (?,?,?,?,?,NOW(),NOW())
      ON DUPLICATE KEY UPDATE external_category_id=VALUES(external_category_id), note=VALUES(note), updated_at=NOW()
    ");
    $header=fgetcsv($h); $count=0;
    while($row=fgetcsv($h)){
      [$path,$ext,$note]=$row;
      $st->execute([$tenant,$mp,$path,$ext,$note]);
      $count++;
    }
    fclose($h);
    return ['ok'=>true,'imported'=>$count];
  }

  // Products Import
  public function importProducts(array $p, array $b, array $q): array {
    if(empty($_FILES['file'])) return ['ok'=>false,'error'=>'no file'];
    $tenant=(int)($q['tenant_id']??0);
    $h=fopen($_FILES['file']['tmp_name'],"r");
    $header=fgetcsv($h);
    $pdo=Database::pdo();
    $prodSt=$pdo->prepare("INSERT INTO products (tenant_id,name,brand,category_path,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW())");
    $varSt=$pdo->prepare("INSERT INTO variants (product_id,sku,price,stock,attrs,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())");
    $mapNameToId=[];
    $count=0;
    while($row=fgetcsv($h)){
      [$name,$brand,$catPath,$sku,$price,$stock,$attrs]=$row;
      $key=$name."|".$brand."|".$catPath;
      if(!isset($mapNameToId[$key])){
        $prodSt->execute([$tenant,$name,$brand,json_encode(explode(">",$catPath))]);
        $pid=(int)$pdo->lastInsertId();
        $mapNameToId[$key]=$pid;
      }
      $pid=$mapNameToId[$key];
      $varSt->execute([$pid,$sku,(float)$price,(int)$stock,$attrs]);
      $count++;
    }
    fclose($h);
    return ['ok'=>true,'imported'=>$count];
  }

  // Products Export
  public function exportProducts(array $p, array $b, array $q): void {
    $tenant=(int)($q['tenant_id']??0);
    if($tenant<=0){ http_response_code(400); echo "tenant_id required"; exit; }

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=products.csv");
    $out=fopen("php://output","w");

    // CSV şeması:
    // product_id,name,brand,category_path,variant_id,sku,price,stock,attrs
    fputcsv($out,["product_id","name","brand","category_path","variant_id","sku","price","stock","attrs"]);

    $pdo=\App\Database::pdo();
    $st=$pdo->prepare("
      SELECT p.id AS product_id, p.name, p.brand, p.category_path, 
             v.id AS variant_id, v.sku, v.price, v.stock, v.attrs
      FROM products p
      LEFT JOIN variants v ON v.product_id=p.id
      WHERE p.tenant_id=?
      ORDER BY p.id ASC, v.id ASC
    ");
    $st->execute([$tenant]);
    while($r=$st->fetch(\PDO::FETCH_ASSOC)){
      $cat=json_decode($r['category_path']??'[]',true);
      $catStr=is_array($cat)? implode('>',$cat) : '';
      fputcsv($out,[
        $r['product_id'],
        $r['name'],
        $r['brand'],
        $catStr,
        $r['variant_id'],
        $r['sku'],
        $r['price'],
        $r['stock'],
        $r['attrs']
      ]);
    }
    fclose($out);
    exit;
  }
}
