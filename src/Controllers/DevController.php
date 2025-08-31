<?php
namespace App\Controllers;
use App\Database;

final class DevController {
  public function wooCats(){
    $c=$this->conn(2); 
    if(!$c) return ['ok'=>false,'error'=>'no_woo_connection'];
    
    $woo=new \App\Integrations\WooAdapter($c);
    return ['ok'=>true,'items'=>$woo->listCategories()];
  }
  
  public function tyTax(){
    $c=$this->conn(1); 
    if(!$c) return ['ok'=>false,'error'=>'no_trendyol_connection'];
    
    $ty=new \App\Integrations\TrendyolAdapter($c);
    return ['ok'=>true,'items'=>$ty->listTaxonomy()];
  }
  
  private function conn($mp){
    $st=\App\Database::pdo()->prepare("SELECT c.*, m.name FROM marketplace_connections c JOIN marketplaces m ON m.id=c.marketplace_id WHERE c.marketplace_id=? ORDER BY id DESC LIMIT 1");
    $st->execute([$mp]); 
    return $st->fetch();
  }
}
