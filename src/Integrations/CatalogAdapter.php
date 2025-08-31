<?php
namespace App\Integrations;
use App\Database;

final class CatalogAdapter {
  public static function fetch(int $mp, int $tenant): array {
    // connection çek
    $pdo=\App\Database::pdo();
    $st=$pdo->prepare("SELECT c.*, m.name FROM marketplace_connections c JOIN marketplaces m ON m.id=c.marketplace_id WHERE c.marketplace_id=? AND c.tenant_id=? ORDER BY id DESC LIMIT 1");
    $st->execute([$mp,$tenant]); 
    $conn=$st->fetch(); 
    if(!$conn) return [];
    
    if($mp===2){ 
      $woo=new \App\Integrations\WooAdapter($conn); 
      return $woo->listCategories(); 
    }
    if($mp===1){ 
      $ty =new \App\Integrations\TrendyolAdapter($conn); 
      return $ty->listTaxonomy(); 
    }
    return [];
  }
}
