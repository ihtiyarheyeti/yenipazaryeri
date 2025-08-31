<?php
namespace App\Controllers;
use App\Database;

final class ReconcileController {
  public function suggest(): array {
    $tenant=\App\Context::$tenantId;
    $pPrice=\App\Utils\Policy::get('price_master',$tenant);
    $pStock=\App\Utils\Policy::get('stock_master',$tenant);
    $thr=\App\Utils\Policy::get('auto_fix_threshold',$tenant);

    // Son 24 saatte toplanan snapshot'lar: woo vs local, trendyol vs local
    $st=Database::pdo()->query("SELECT r.product_id,r.variant_id,
      MAX(CASE WHEN r.source='woo' THEN r.price END) as woo_price,
      MAX(CASE WHEN r.source='trendyol' THEN r.price END) as ty_price,
      MAX(CASE WHEN r.source='woo' THEN r.stock END) as woo_stock,
      MAX(CASE WHEN r.source='trendyol' THEN r.stock END) as ty_stock
      FROM reconcile_snapshots r
      WHERE r.taken_at > NOW() - INTERVAL 1 DAY
      GROUP BY r.product_id,r.variant_id
      ORDER BY r.product_id DESC LIMIT 1000");
    $rows=$st->fetchAll();

    $ins=0;
    foreach($rows as $x){
      // local değerleri oku
      $v=Database::pdo()->prepare("SELECT price,stock FROM variants WHERE id=?"); 
      $v->execute([(int)$x['variant_id']]); 
      $loc=$v->fetch();
      if(!$loc) continue;

      // price mismatch
      foreach([['src'=>'woo','val'=>$x['woo_price']],['src'=>'trendyol','val'=>$x['ty_price']]] as $pp){
        if($pp['val']!==null){
          $diff = abs((float)$pp['val'] - (float)$loc['price']);
          $ratio = (float)$loc['price']>0 ? $diff/(float)$loc['price'] : 0;
          if($ratio > (float)($thr['price']??0.02)){
            $suggest = ($pPrice['master']??'local')==='local' ? "set {$pp['src']} to local" : "set local to {$pp['src']}";
            Database::pdo()->prepare("INSERT INTO reconcile_suggestions (tenant_id,product_id,variant_id,issue,local_value,remote_value,source,suggestion)
              VALUES (?,?,?,?,?,?,?,?)")
              ->execute([$tenant,$x['product_id'],$x['variant_id'],'price_mismatch',(string)$loc['price'],(string)$pp['val'],$pp['src'],$suggest]);
            $ins++;
          }
        }
      }
      // stock mismatch
      foreach([['src'=>'woo','val'=>$x['woo_stock']],['src'=>'trendyol','val'=>$x['ty_stock']]] as $ss){
        if($ss['val']!==null && (int)$ss['val'] !== (int)$loc['stock']){
          $suggest = ($pStock['master']??'local')==='local' ? "set {$ss['src']} to local" : "set local to {$ss['src']}";
          Database::pdo()->prepare("INSERT INTO reconcile_suggestions (tenant_id,product_id,variant_id,issue,local_value,remote_value,source,suggestion)
            VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$tenant,$x['product_id'],$x['variant_id'],'stock_mismatch',(string)$loc['stock'],(string)$ss['val'],$ss['src'],$suggest]);
          $ins++;
        }
      }
    }
    return ['ok'=>true,'inserted'=>$ins];
  }

  public function resolve(array $p,array $b): array {
    $id=(int)$p[0]; 
    $note=$b['note']??null;
    \App\Database::pdo()->prepare("UPDATE reconcile_suggestions SET resolved_at=NOW(), resolution_note=? WHERE id=?")
      ->execute([$note,$id]);
    return ['ok'=>true];
  }
}
