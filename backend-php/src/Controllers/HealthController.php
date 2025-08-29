<?php
namespace App\Controllers;
use App\Database;

final class HealthController {
  public function liveness(): array { 
    return ['ok'=>true,'time'=>date('c'),'ts'=>time()]; 
  }
  
  public function readiness(): array {
    try{ 
      Database::pdo()->query("SELECT 1"); 
      return ['ok'=>true,'time'=>date('c')]; 
    }
    catch(\Throwable $e){ 
      http_response_code(500); 
      return ['ok'=>false,'error'=>'db','time'=>date('c')]; 
    }
  }
  
  public function metrics(): array {
    try {
      $pdo = Database::pdo();
      
      // Ürün sayıları
      $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
      $variantCount = $pdo->query("SELECT COUNT(*) FROM variants")->fetchColumn();
      
      // Origin bazlı ürün sayıları
      $originStats = $pdo->query("SELECT origin_mp, COUNT(*) as count FROM products GROUP BY origin_mp")->fetchAll();
      
      // Mapping durumu
      $mappedProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE category_match = 'mapped'")->fetchColumn();
      $unmappedProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE category_match = 'unmapped'")->fetchColumn();
      
      return [
        'ok' => true,
        'metrics' => [
          'products' => [
            'total' => (int)$productCount,
            'variants' => (int)$variantCount,
            'by_origin' => $originStats,
            'mapping' => [
              'mapped' => (int)$mappedProducts,
              'unmapped' => (int)$unmappedProducts
            ]
          ]
        ]
      ];
    } catch(\Throwable $e) {
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }
}
