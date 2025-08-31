<?php
namespace App\Utils;
use App\Database;

final class Credentials {
  // Trendyol: supplier_id, api_key, api_secret çek
  public static function trendyol(int $tenantId): ?array {
    $pdo=Database::pdo();
    $st=$pdo->prepare("
      SELECT mc.api_key, mc.api_secret, mc.supplier_id
      FROM marketplace_connections mc
      JOIN marketplaces m ON m.id=mc.marketplace_id
      WHERE mc.tenant_id=? AND (mc.marketplace_id=1 OR m.name='Trendyol')
      ORDER BY mc.id DESC LIMIT 1
    ");
    $st->execute([$tenantId]);
    $c=$st->fetch();
    if(!$c) return null;
    if(empty($c['api_key'])||empty($c['api_secret'])||empty($c['supplier_id'])) return null;
    return ['supplier_id'=>(int)$c['supplier_id'],'api_key'=>$c['api_key'],'api_secret'=>$c['api_secret']];
  }

  // Woo: base_url, key, secret çek
  public static function woo(int $tenantId): ?array {
    $pdo=Database::pdo();
    $st=$pdo->prepare("
      SELECT mc.api_key, mc.api_secret, m.base_url
      FROM marketplace_connections mc
      JOIN marketplaces m ON m.id=mc.marketplace_id
      WHERE mc.tenant_id=? AND (mc.marketplace_id=2 OR m.name='WooCommerce')
      ORDER BY mc.id DESC LIMIT 1
    ");
    $st->execute([$tenantId]);
    $c=$st->fetch();
    if(!$c) return null;
    if(empty($c['api_key'])||empty($c['api_secret'])||empty($c['base_url'])) return null;
    return ['base_url'=>$c['base_url'],'api_key'=>$c['api_key'],'api_secret'=>$c['api_secret']];
  }
}
