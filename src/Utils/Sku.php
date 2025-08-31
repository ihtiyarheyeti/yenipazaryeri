<?php
namespace App\Utils;
use App\Database;

final class Sku {
  /** Boş/tekrarlı SKUlarda otomatik üretim; benzersiz olana kadar sayıcı ekler */
  public static function ensure(string $basePrefix, int $tenantId, ?string $desired=null): string {
    $pdo=Database::pdo();
    // 1) aday: girilen SKU (trim)
    if($desired){
      $sku=trim($desired);
      if($sku==='') $sku=null;
      if($sku && self::isFree($tenantId,$sku)) return $sku;
    }
    // 2) base prefix + zaman ve küçük hash
    $prefix = preg_replace('/[^A-Za-z0-9\-]/','-', $basePrefix ?: 'AUTO');
    $prefix = trim($prefix,'-');
    $seed   = strtoupper($prefix).'-'.date('ymd');
    $i=1;
    do{
      $cand = $seed.'-'.str_pad((string)$i, 3, '0', STR_PAD_LEFT);
      if(self::isFree($tenantId,$cand)) return $cand;
      $i++;
    }while($i<10000);
    // 3) son çare: uniqid
    return strtoupper($prefix).'-'.strtoupper(substr(sha1(uniqid('',true)),0,8));
  }

  public static function isFree(int $tenantId, string $sku): bool {
    // Variants tablosunda tenant_id yok, product_id üzerinden kontrol et
    $st=Database::pdo()->prepare("SELECT 1 FROM variants v JOIN products p ON v.product_id = p.id WHERE p.tenant_id=? AND v.sku=? LIMIT 1");
    $st->execute([$tenantId, $sku]);
    return $st->fetchColumn() ? false : true;
  }
}
