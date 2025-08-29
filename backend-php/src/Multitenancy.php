<?php
namespace App;
use App\Database;

final class Multitenancy {
  public static function currentTenantId(): int {
    // 1) ENV ile zorla (örn. CLI worker)
    $forced = getenv('FORCED_TENANT_ID');
    if ($forced) return (int)$forced;

    // 2) Host'tan subdomain çıkar
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // app.localhost:8000 → app
    $sub = explode('.', preg_replace('/:\d+$/','',$host))[0] ?? 'app';
    // localhost için .env fallback
    if (in_array($sub, ['localhost','127','0'])) $sub = getenv('DEV_SUBDOMAIN') ?: 'app';

    // 3) DB'den tenant id getir (cache basit)
    static $cache = [];
    if (isset($cache[$sub])) return $cache[$sub];

    $st = Database::pdo()->prepare("SELECT id FROM tenants WHERE slug=? LIMIT 1");
    $st->execute([$sub]); $id = (int)($st->fetchColumn() ?: 1);
    return $cache[$sub] = $id;
  }
}
