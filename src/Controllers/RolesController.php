<?php
namespace App\Controllers;
use App\Database;
use App\Middleware\Authorize;
use App\Middleware\Audit;
use App\Context;

final class RolesController {
  private ?int $userId = null;
  
  public function __construct() {
    // JWT'den user ID'yi al (basit implementasyon)
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $auth, $m)) {
      $token = $m[1];
      // Basit JWT decode (gerçek implementasyonda JWT library kullan)
      $parts = explode('.', $token);
      if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
        $this->userId = $payload['user_id'] ?? null;
      }
    }
  }
  
  public function index(array $p, array $b, array $q): array {
    $uid = $this->userId ?? 0; 
    if(!Authorize::can($uid, 'roles.read')){ 
      http_response_code(403); 
      return ['ok'=>false,'error'=>'forbidden']; 
    }
    
    $tenant = (int)($q['tenant_id'] ?? Context::$tenantId);
    $st = Database::pdo()->prepare("SELECT id,name FROM roles WHERE tenant_id=? ORDER BY name ASC");
    $st->execute([$tenant]); 
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }

  public function create(array $p, array $b): array {
    $uid = $this->userId ?? 0; 
    if(!Authorize::can($uid, 'roles.write')){ 
      http_response_code(403); 
      return ['ok'=>false,'error'=>'forbidden']; 
    }
    
    $tenant = (int)($b['tenant_id'] ?? Context::$tenantId); 
    $name = trim((string)($b['name'] ?? ''));
    
    if($name === ''){ 
      http_response_code(422); 
      return ['ok'=>false,'error'=>'validation','fields'=>['name'=>'required']]; 
    }
    
    \App\Database::pdo()->prepare("INSERT INTO roles(tenant_id,name) VALUES(?,?)")->execute([$tenant,$name]);
    Audit::log($uid, 'role.create', '/roles', ['name'=>$name]);
    return ['ok'=>true];
  }

  public function delete(array $p): array {
    $uid = $this->userId ?? 0; 
    if(!Authorize::can($uid, 'roles.write')){ 
      http_response_code(403); 
      return ['ok'=>false,'error'=>'forbidden']; 
    }
    
    $id = (int)$p[0]; 
    \App\Database::pdo()->prepare("DELETE FROM roles WHERE id=?")->execute([$id]);
    Audit::log($uid, 'role.delete', "/roles/$id");
    return ['ok'=>true];
  }

  public function getPermissions(array $p): array {
    $rid = (int)$p[0];
    $pdo = Database::pdo();
    $all = $pdo->query("SELECT id,name FROM permissions ORDER BY name ASC")->fetchAll();
    $sel = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id=?");
    $sel->execute([$rid]); 
    $have = array_map('intval', array_column($sel->fetchAll(), 'permission_id'));
    return ['ok'=>true,'all'=>$all,'selected'=>$have];
  }

  public function setPermissions(array $p, array $b): array {
    $uid = $this->userId ?? 0; 
    if(!\App\Middleware\Authorize::can($uid, 'roles.write')){ 
      http_response_code(403); 
      return ['ok'=>false,'error'=>'forbidden']; 
    }
    
    $rid = (int)$p[0]; 
    $permIds = $b['permission_ids'] ?? [];
    
    $pdo = Database::pdo(); 
    $pdo->prepare("DELETE FROM role_permissions WHERE role_id=?")->execute([$rid]);
    
    foreach($permIds as $pid){ 
      $pdo->prepare("INSERT IGNORE INTO role_permissions(role_id,permission_id) VALUES(?,?)")->execute([$rid,(int)$pid]); 
    }
    
    \App\Middleware\Audit::log($uid, 'role.set_permissions', "/roles/$rid", ['perm_ids'=>$permIds]);
    return ['ok'=>true];
  }
}
