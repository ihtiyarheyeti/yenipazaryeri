<?php
namespace App\Utils;

final class AttrMap {
  /**
   * Local attribute'ları external marketplace formatına dönüştür
   */
  public static function toExternal(array $localAttrs, int $marketplaceId): array {
    $pdo = \App\Database::pdo();
    $tenant = \App\Context::$tenantId;
    
    $extAttrs = [];
    foreach($localAttrs as $localKey => $localValue) {
      // Attribute mapping tablosundan external key'i bul
      $st = $pdo->prepare("SELECT external_key, value_map FROM attribute_mapping 
                           WHERE tenant_id=? AND marketplace_id=? AND local_key=?");
      $st->execute([$tenant, $marketplaceId, $localKey]);
      $mapping = $st->fetch();
      
      if($mapping) {
        $extKey = $mapping['external_key'];
        $extValue = $localValue;
        
        // Value mapping varsa uygula
        if($mapping['value_map']) {
          $valueMap = json_decode($mapping['value_map'], true);
          if(is_array($valueMap) && isset($valueMap[$localValue])) {
            $extValue = $valueMap[$localValue];
          }
        }
        
        $extAttrs[$extKey] = $extValue;
      } else {
        // Mapping yoksa local key'i kullan
        $extAttrs[$localKey] = $localValue;
      }
    }
    
    return $extAttrs;
  }

  /**
   * External attribute'ları local formatına dönüştür
   */
  public static function toLocal(array $extAttrs, int $marketplaceId): array {
    $pdo = \App\Database::pdo();
    $tenant = \App\Context::$tenantId;
    
    $localAttrs = [];
    foreach($extAttrs as $extKey => $extValue) {
      // Reverse mapping tablosundan local key'i bul
      $st = $pdo->prepare("SELECT local_key, value_map FROM attribute_mapping 
                           WHERE tenant_id=? AND marketplace_id=? AND external_key=?");
      $st->execute([$tenant, $marketplaceId, $extKey]);
      $mapping = $st->fetch();
      
      if($mapping) {
        $localKey = $mapping['local_key'];
        $localValue = $extValue;
        
        // Reverse value mapping varsa uygula
        if($mapping['value_map']) {
          $valueMap = json_decode($mapping['value_map'], true);
          if(is_array($valueMap)) {
            $reverseMap = array_flip($valueMap);
            if(isset($reverseMap[$extValue])) {
              $localValue = $reverseMap[$extValue];
            }
          }
        }
        
        $localAttrs[$localKey] = $localValue;
      } else {
        // Mapping yoksa external key'i kullan
        $localAttrs[$extKey] = $extValue;
      }
    }
    
    return $localAttrs;
  }
}
