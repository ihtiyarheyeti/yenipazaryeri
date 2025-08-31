<?php
namespace App\Controllers;

use App\Database;

final class CategoryMappingController {
  public function index(array $p, array $b, array $q): array {
    $tenant = (int)($q['tenant_id'] ?? 0); 
    $mp = (int)($q['marketplace_id'] ?? 0);
    $page = max(1, (int)($q['page'] ?? 1)); 
    $ps = min(100, max(1, (int)($q['pageSize'] ?? 10))); 
    $off = ($page - 1) * $ps;
    $qstr = trim((string)($q['q'] ?? ''));
    
    $where = "WHERE 1=1"; 
    $bind = [];
    
    if($tenant > 0) { 
      $where .= " AND cm.tenant_id=?"; 
      $bind[] = $tenant; 
    }
    
    if($mp > 0) { 
      $where .= " AND cm.marketplace_id=?"; 
      $bind[] = $mp; 
    }
    
    if($qstr !== '') { 
      $where .= " AND (cm.source_path LIKE ? OR cm.external_category_id LIKE ?)"; 
      $bind[] = "%$qstr%"; 
      $bind[] = "%$qstr%"; 
    }

    $cnt = Database::pdo()->prepare("SELECT COUNT(*) FROM category_mappings cm $where");
    $cnt->execute($bind); 
    $total = (int)$cnt->fetchColumn();

    $sql = "SELECT cm.*, m.name AS marketplace_name
            FROM category_mappings cm
            JOIN marketplaces m ON m.id=cm.marketplace_id
            $where ORDER BY cm.id DESC LIMIT $off,$ps";
    $st = Database::pdo()->prepare($sql); 
    $st->execute($bind);
    
    return ['ok' => true, 'items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pageSize' => $ps];
  }

  public function create(array $p, array $b): array {
    $pdo = Database::pdo();
    $st = $pdo->prepare("INSERT INTO category_mappings (tenant_id,marketplace_id,source_path,external_category_id,note,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())");
    $st->execute([(int)$b['tenant_id'], (int)$b['marketplace_id'], $b['source_path'] ?? '', $b['external_category_id'] ?? '', $b['note'] ?? null]);
    return ['ok' => true, 'id' => $pdo->lastInsertId()];
  }

  public function update(array $p, array $b): array {
    $id = (int)$p[0]; 
    if($id <= 0) return ['ok' => false, 'error' => 'invalid id'];
    
    $st = Database::pdo()->prepare("UPDATE category_mappings SET source_path=?, external_category_id=?, note=?, updated_at=NOW() WHERE id=?");
    $st->execute([$b['source_path'] ?? '', $b['external_category_id'] ?? '', $b['note'] ?? null, $id]);
    return ['ok' => true];
  }

  public function delete(array $p, array $b): array {
    $id = (int)$p[0]; 
    if($id <= 0) return ['ok' => false, 'error' => 'invalid id'];
    
    $st = Database::pdo()->prepare("DELETE FROM category_mappings WHERE id=?"); 
    $st->execute([$id]);
    return ['ok' => true];
  }

  // CSV EXPORT: text/csv
  public function exportCsv(array $p, array $b, array $q) {
    $tenant = (int)($q['tenant_id'] ?? 0); 
    $mp = (int)($q['marketplace_id'] ?? 0);
    $where = "WHERE 1=1"; 
    $bind = [];
    
    if($tenant > 0) { 
      $where .= " AND tenant_id=?"; 
      $bind[] = $tenant; 
    }
    
    if($mp > 0) { 
      $where .= " AND marketplace_id=?"; 
      $bind[] = $mp; 
    }

    $st = Database::pdo()->prepare("SELECT marketplace_id,source_path,external_category_id,note FROM category_mappings $where ORDER BY id DESC");
    $st->execute($bind);
    
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=category_mappings.csv");
    
    $out = fopen("php://output", "w");
    fputcsv($out, ["marketplace_id", "source_path", "external_category_id", "note"], ';');
    
    while($r = $st->fetch(\PDO::FETCH_ASSOC)) { 
      fputcsv($out, $r, ';'); 
    }
    
    fclose($out); 
    exit;
  }

  // CSV IMPORT: multipart/form-data, ; ayırıcı
  public function importCsv(array $p, array $b, array $q): array {
    $tenant = (int)($q['tenant_id'] ?? 0); 
    if($tenant <= 0) return ['ok' => false, 'error' => 'tenant_id required'];
    
    if(!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      return ['ok' => false, 'error' => 'file required'];
    }
    
    $fp = fopen($_FILES['file']['tmp_name'], 'r'); 
    if(!$fp) return ['ok' => false, 'error' => 'cant open file'];
    
    // başlığı oku
    $header = fgetcsv($fp, 0, ';');
    $pdo = Database::pdo(); 
    $ins = 0; 
    $upd = 0;
    
    while(($row = fgetcsv($fp, 0, ';')) !== false) {
      $rec = array_combine($header, $row);
      $mp = (int)($rec['marketplace_id'] ?? 0);
      $src = trim($rec['source_path'] ?? '');
      $ext = trim($rec['external_category_id'] ?? '');
      $note = $rec['note'] ?? null;
      
      if($mp > 0 && $src !== '' && $ext !== '') {
        // unique: tenant+mp+source_path
        $sel = $pdo->prepare("SELECT id FROM category_mappings WHERE tenant_id=? AND marketplace_id=? AND source_path=?");
        $sel->execute([$tenant, $mp, $src]); 
        $id = $sel->fetchColumn();
        
        if($id) {
          $pdo->prepare("UPDATE category_mappings SET external_category_id=?, note=?, updated_at=NOW() WHERE id=?")->execute([$ext, $note, $id]); 
          $upd++;
        } else {
          $pdo->prepare("INSERT INTO category_mappings (tenant_id,marketplace_id,source_path,external_category_id,note,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())")->execute([$tenant, $mp, $src, $ext, $note]); 
          $ins++;
        }
      }
    }
    
    fclose($fp);
    return ['ok' => true, 'inserted' => $ins, 'updated' => $upd];
  }
}
