<?php
namespace App\Controllers;
use App\Database;

final class BatchController {
  public function list(array $p,array $b,array $q): array {
    $st=Database::pdo()->query("SELECT batch_id, COUNT(*) as jobs, SUM(status='done') as done, SUM(status='error') as errors, MIN(created_at) as started, MAX(updated_at) as last_update FROM jobs WHERE batch_id IS NOT NULL GROUP BY batch_id ORDER BY started DESC LIMIT 100");
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }
  
  public function detail(array $p): array {
    $bid=$p[0]??'';
    $st=Database::pdo()->prepare("SELECT id,type,status,attempts,last_error,created_at,updated_at FROM jobs WHERE batch_id=? ORDER BY id DESC LIMIT 500");
    $st->execute([$bid]); 
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }
}
