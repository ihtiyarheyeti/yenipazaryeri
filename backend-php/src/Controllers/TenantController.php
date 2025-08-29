<?php
namespace App\Controllers;
use App\Database;

final class TenantController {
  public function brandingGet(array $p, array $b, array $q): array {
    $id=(int)($q['tenant_id']??1);
    $st=Database::pdo()->prepare("SELECT id,name,logo_url,theme_primary,theme_accent,theme_mode FROM tenants WHERE id=?");
    $st->execute([$id]); $row=$st->fetch();
    return ['ok'=>true,'item'=>$row];
  }
  public function brandingSet(array $p, array $b): array {
    $id=(int)($b['tenant_id']??1);
    $st=Database::pdo()->prepare("UPDATE tenants SET logo_url=?, theme_primary=?, theme_accent=?, theme_mode=?, name=COALESCE(?,name) WHERE id=?");
    $st->execute([$b['logo_url']??null, $b['theme_primary']??null, $b['theme_accent']??null, $b['theme_mode']??'light', $b['name']??null, $id]);
    return ['ok'=>true];
  }
}
