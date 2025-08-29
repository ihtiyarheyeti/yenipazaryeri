<?php
namespace App\Standardizer;
use App\Database;

final class Mapper {
  /** category_external (örn TY categoryId) → local category_path */
  public static function categoryToLocal(string $origin, ?string $ext, int $tenant): ?string {
    if(!$ext) return null;
    $mp = $origin==='trendyol' ? 1 : 2;
    $st=Database::pdo()->prepare("SELECT local_path FROM category_mapping WHERE tenant_id=? AND marketplace_id=? AND external_id=?");
    $st->execute([$tenant,$mp,$ext]); 
    return $st->fetchColumn() ?: null;
  }
  
  /** attrs key/value'lerini gerekirse origin'e göre normalize et (şimdilik aynen döndür) */
  public static function normalizeAttrs(array $attrs): array { 
    return $attrs; 
  }
}

