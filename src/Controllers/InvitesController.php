<?php
namespace App\Controllers;
use App\Database;
use App\Utils\Mailer;
use App\Middleware\Authorize;
use App\Middleware\Audit;
use App\Context;

final class InvitesController {
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
  
  public function create(array $p, array $b): array {
    $uid = $this->userId ?? 0; 
    if(!Authorize::can($uid, 'users.write')){ 
      http_response_code(403); 
      return ['ok'=>false,'error'=>'forbidden']; 
    }
    
    $tenant = (int)($b['tenant_id'] ?? Context::$tenantId);
    $email = trim((string)($b['email'] ?? '')); 
    $roleId = (int)($b['role_id'] ?? 0);
    
    if($email === ''){ 
      http_response_code(422); 
      return ['ok'=>false,'error'=>'validation','fields'=>['email'=>'required']]; 
    }
    
    $tok = bin2hex(random_bytes(16));
    $exp = date('Y-m-d H:i:s', time()+60*60*24*7);
    
    $pdo = Database::pdo();
    $pdo->prepare("INSERT INTO invites(tenant_id,email,token,role_id,expires_at) VALUES(?,?,?,?,?) 
                   ON DUPLICATE KEY UPDATE token=VALUES(token), role_id=VALUES(role_id), expires_at=VALUES(expires_at), accepted_at=NULL")
        ->execute([$tenant,$email,$tok,$roleId?:null,$exp]);
    
    $link = (getenv('APP_BASE_URL') ?: 'http://localhost:3000')."/invite?token=$tok";
    Mailer::rawSend($email, "Davet", "Hesabınızı oluşturmak için bağlantı: $link");
    
    Audit::log($uid, 'invite.create', '/invites', ['email'=>$email]);
    return ['ok'=>true];
  }

  public function accept(array $p, array $b): array {
    $token = $b['token'] ?? ''; 
    $name = trim((string)($b['name'] ?? '')); 
    $password = $b['password'] ?? '';
    
    $pdo = Database::pdo();
    $st = $pdo->prepare("SELECT * FROM invites WHERE token=? AND accepted_at IS NULL AND expires_at>NOW()");
    $st->execute([$token]); 
    $inv = $st->fetch(); 
    
    if(!$inv){ 
      http_response_code(400); 
      return ['ok'=>false,'error'=>'invalid_or_expired']; 
    }

    // kullanıcı oluştur
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users(tenant_id,name,email,password_hash,created_at,updated_at) VALUES (?,?,?,?, NOW(), NOW())")
        ->execute([$inv['tenant_id'], $name ?: $inv['email'], $inv['email'], $hash]);
    
    $uid = (int)$pdo->lastInsertId();
    
    if($inv['role_id']){ 
      $pdo->prepare("INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)")->execute([$uid,(int)$inv['role_id']]); 
    }
    
    $pdo->prepare("UPDATE invites SET accepted_at=NOW() WHERE id=?")->execute([$inv['id']]);
    
    Audit::log($uid, 'invite.accept', '/invite', []);
    return ['ok'=>true];
  }
}
