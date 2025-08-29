<?php
namespace App\Controllers;
use App\Database;

final class ProductCsvController {
  public function export(array $p,array $b,array $q){
    $tenant=(int)($q['tenant_id']??1);
    $st=Database::pdo()->prepare("SELECT p.id,p.name,p.brand,p.description,p.category_path, v.sku,v.price,v.stock,v.attrs
                                  FROM products p LEFT JOIN variants v ON v.product_id=p.id
                                  WHERE p.tenant_id=? ORDER BY p.id DESC");
    $st->execute([$tenant]);
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=products.csv");
    $out=fopen("php://output","w");
    fputcsv($out,["name","brand","description","category_path","sku","price","stock","attrs_json"],';');
    while($r=$st->fetch(\PDO::FETCH_ASSOC)){
      $cat = $r['category_path'] ? implode('>', json_decode($r['category_path'],true)): '';
      fputcsv($out, [$r['name'],$r['brand'],$r['description'],$cat,$r['sku'],$r['price'],$r['stock'],$r['attrs']], ';');
    }
    fclose($out); exit;
  }

  public function import(array $p,array $b,array $q): array {
    $tenant=(int)($q['tenant_id']??1);
    if(!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) return ['ok'=>false,'error'=>'file'];
    $fp=fopen($_FILES['file']['tmp_name'],'r'); if(!$fp) return ['ok'=>false,'error'=>'open'];
    $hdr=fgetcsv($fp,0,';'); $ins=0; $upd=0;
    $pdo=Database::pdo();
    while(($row=fgetcsv($fp,0,';'))!==false){
      $rec=@array_combine($hdr,$row);
      $name=trim($rec['name']??''); if($name==='') continue;
      $brand=$rec['brand']??null; $desc=$rec['description']??null;
      $cat=$rec['category_path']??''; $catJson = $cat? json_encode(array_map('trim', explode('>',$cat))) : null;
      $sku=$rec['sku']??null; $price=(float)($rec['price']??0); $stock=(int)($rec['stock']??0);
      $attrs=$rec['attrs_json']??null;

      // ürün var mı (name + tenant bazlı basit arama)
      $sel=$pdo->prepare("SELECT id FROM products WHERE tenant_id=? AND name=?");
      $sel->execute([$tenant,$name]); $pid=$sel->fetchColumn();

      if($pid){
        $pdo->prepare("UPDATE products SET brand=?, description=?, category_path=?, updated_at=NOW() WHERE id=?")
            ->execute([$brand,$desc,$catJson,$pid]); $upd++;
      } else {
        $pdo->prepare("INSERT INTO products (tenant_id,name,brand,description,category_path,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())")
            ->execute([$tenant,$name,$brand,$desc,$catJson]); $pid=$pdo->lastInsertId(); $ins++;
      }

      if($sku){
        // varyantı upsert et (sku benzersiz gibi davran)
        $selv=$pdo->prepare("SELECT id FROM variants WHERE product_id=? AND sku=?");
        $selv->execute([$pid,$sku]); $vid=$selv->fetchColumn();
        $attrsJson = $attrs ?: null;
        if($vid){
          $pdo->prepare("UPDATE variants SET price=?, stock=?, attrs=?, updated_at=NOW() WHERE id=?")
              ->execute([$price,$stock,$attrsJson,$vid]);
        } else {
          $pdo->prepare("INSERT INTO variants (product_id,sku,price,stock,attrs,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())")
              ->execute([$pid,$sku,$price,$stock,$attrsJson]);
        }
      }
    }
    fclose($fp);
    return ['ok'=>true,'inserted'=>$ins,'updated'=>$upd];
  }

  public function validate(array $p,array $b,array $q): array {
    if(!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) return ['ok'=>false,'error'=>'file'];
    $fp=fopen($_FILES['file']['tmp_name'],'r'); if(!$fp) return ['ok'=>false,'error'=>'open'];
    $hdr=fgetcsv($fp,0,';'); $rowNum=1; $errors=[]; $okCount=0;
    $required=['name']; $allowed=['name','brand','description','category_path','sku','price','stock','attrs_json','status'];
    while(($row=fgetcsv($fp,0,';'))!==false){
      $rowNum++;
      $rec=@array_combine($hdr,$row) ?: [];
      $rowErr=[];
      // zorunlular
      foreach($required as $f){ if(empty(trim((string)($rec[$f]??'')))) $rowErr[]=['field'=>$f,'code'=>'required','msg'=>'zorunlu']; }
      // tip kontrolleri
      if(isset($rec['price']) && $rec['price']!=='' && !is_numeric($rec['price'])) $rowErr[]=['field'=>'price','code'=>'number','msg'=>'sayı olmalı'];
      if(isset($rec['stock']) && $rec['stock']!=='' && !is_numeric($rec['stock'])) $rowErr[]=['field'=>'stock','code'=>'number','msg'=>'sayı olmalı'];
      if(isset($rec['status']) && $rec['status']!=='' && !in_array($rec['status'],['draft','active','archived'])) $rowErr[]=['field'=>'status','code'=>'enum','msg'=>'draft|active|archived'];
      // bilinmeyen kolon uyarısı
      foreach(array_keys($rec) as $k){ if($k && !in_array($k,$allowed,true)) $rowErr[]=['field'=>$k,'code'=>'unknown_col','msg'=>'tanımsız kolon']; }
      if($rowErr){ $errors[]=['row'=>$rowNum,'errors'=>$rowErr]; } else { $okCount++; }
    }
    fclose($fp);
    return ['ok'=>true,'valid_rows'=>$okCount,'errors'=>$errors];
  }

  public function importAndSync(array $p, array $b, array $q): array {
    if(!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) return ['ok'=>false,'error'=>'file'];
    $fp=fopen($_FILES['file']['tmp_name'],'r'); if(!$fp) return ['ok'=>false,'error'=>'open'];
    $hdr=fgetcsv($fp,0,';'); $rowNum=1; $productIds=[];
    
    $pdo = Database::pdo();
    $tenant = \App\Context::$tenantId;
    
    while(($row=fgetcsv($fp,0,';'))!==false){
      $rowNum++; 
      $rec=@array_combine($hdr,$row) ?: [];
      $name=trim((string)($rec['name']??'')); if(!$name) continue;
      $sku =trim((string)($rec['sku']??'')); $price=(float)($rec['price']??0); $stock=(int)($rec['stock']??0);
      $brand=trim((string)($rec['brand']??'')); $desc=(string)($rec['description']??'');
      $cat =trim((string)($rec['category_path']??'')); $attrs = !empty($rec['attrs_json'])? json_decode($rec['attrs_json'],true):[];

      // ürün var mı?
      $st=$pdo->prepare("SELECT id FROM products WHERE name=? AND tenant_id=? LIMIT 1"); 
      $st->execute([$name, $tenant]); 
      $pid=(int)($st->fetchColumn()?:0);
      
      if(!$pid){
        $pdo->prepare("INSERT INTO products(tenant_id,name,brand,description,category_path,status,created_at,updated_at) VALUES (?,?,?,?,?,'draft',NOW(),NOW())")
          ->execute([$tenant,$name,$brand,$desc,$cat]);
        $pid=(int)$pdo->lastInsertId();
      } else {
        $pdo->prepare("UPDATE products SET brand=?, description=?, category_path=?, updated_at=NOW() WHERE id=?")
          ->execute([$brand,$desc,$cat,$pid]);
      }

      // varyant (SKU unique varsayalım)
      if($sku){
        $vv=$pdo->prepare("SELECT id FROM variants WHERE sku=? AND product_id=?"); 
        $vv->execute([$sku, $pid]); 
        $vid=(int)($vv->fetchColumn()?:0);
        
        if(!$vid){
          $pdo->prepare("INSERT INTO variants(product_id, sku, price, stock, attrs_json, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())")
            ->execute([$pid,$sku,$price,$stock, json_encode($attrs,JSON_UNESCAPED_UNICODE)]);
        } else {
          $pdo->prepare("UPDATE variants SET price=?, stock=?, attrs_json=?, updated_at=NOW() WHERE id=?")
            ->execute([$price,$stock, json_encode($attrs,JSON_UNESCAPED_UNICODE), $vid]);
        }
      }
      $productIds[$pid]=true;
    }
    fclose($fp);
    
    // Senkron işlerini sıraya al (varsayılan: her iki hedef)
    $ids=array_map('intval', array_keys($productIds));
    $pdo->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('csv_bulk_sync','pending',?,NOW())")
      ->execute([json_encode(['product_ids'=>$ids,'targets'=>['woo','trendyol']])]);
    
    return ['ok'=>true,'imported'=>count($ids),'enqueued'=>count($ids)];
  }
}
