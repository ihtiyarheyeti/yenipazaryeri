<?php
namespace App\Controllers;
use App\Database;

final class LogController {
  public function index(array $p, array $b, array $q): array {
    $pdo = Database::pdo();

    $tenantId = (int)($q['tenant_id'] ?? 0);
    if ($tenantId <= 0) return ['ok'=>false,'error'=>'tenant_id required'];

    $page = max(1,(int)($q['page']??1));
    $pageSize = min(50,max(1,(int)($q['pageSize']??10)));
    $offset = ($page-1)*$pageSize;
    $type = $q['type'] ?? null;

    $where = "WHERE tenant_id=:t";
    $bind = [':t'=>$tenantId];
    if ($type) { $where .= " AND type=:type"; $bind[':type']=$type; }

    $countSt=$pdo->prepare("SELECT COUNT(*) FROM logs $where");
    $countSt->execute($bind);
    $total=(int)$countSt->fetchColumn();

    $st=$pdo->prepare("SELECT * FROM logs $where ORDER BY id DESC LIMIT $offset,$pageSize");
    $st->execute($bind);
    $items=$st->fetchAll();

    return ['ok'=>true,'items'=>$items,'page'=>$page,'pageSize'=>$pageSize,'total'=>$total];
  }

  public function export(){
    $f=__DIR__.'/../../storage/logs/app.jsonl';
    if(!file_exists($f)){ http_response_code(404); return ['ok'=>false,'error'=>'not_found']; }
    header("Content-Type: application/json");
    header("Content-Disposition: attachment; filename=app.jsonl");
    readfile($f); exit;
  }
}
