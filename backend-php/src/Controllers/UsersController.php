<?php
namespace App\Controllers;
use App\Database;
use App\Middleware\Authorize;
use App\Middleware\Audit;
use App\Context;

final class UsersController {
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
    if(!Authorize::can($uid, 'users.read')){ 
      http_response_code(403); 
      return ['ok'=>false,'error'=>'forbidden']; 
    }
    
    $tenant = (int)($q['tenant_id'] ?? Context::$tenantId);
    $qstr = trim((string)($q['q']??''));
    $where = "WHERE u.tenant_id=?";
    $bind = [$tenant];
    
    if($qstr !== ''){ 
      $where .= " AND (u.name LIKE ? OR u.email LIKE ?)"; 
      $bind[] = "%$qstr%"; 
      $bind[] = "%$qstr%"; 
    }
    
    $sql = "SELECT u.id,u.name,u.email,u.created_at,
                   GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') AS roles
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id=u.id
            LEFT JOIN roles r ON r.id=ur.role_id
            $where
            GROUP BY u.id
            ORDER BY u.id DESC
            LIMIT 200";
            
    $st = Database::pdo()->prepare($sql); 
    $st->execute($bind);
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }

  public function setRoles(array $p, array $b): array {
    $uid = $this->userId ?? 0;
    if(!Authorize::can($uid, 'users.write')){ 
      http_response_code(403); 
      return ['ok'=>false,'error'=>'forbidden']; 
    }
    
    $userId = (int)$p[0]; 
    $roleIds = $b['role_ids'] ?? [];
    
    $pdo = Database::pdo(); 
    $pdo->prepare("DELETE FROM user_roles WHERE user_id=?")->execute([$userId]);
    
    foreach($roleIds as $rid){ 
      $pdo->prepare("INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)")->execute([$userId,(int)$rid]); 
    }
    
    Audit::log($uid, 'user.set_roles', "/users/$userId", ['roles'=>$roleIds]);
    return ['ok'=>true];
  }
}
