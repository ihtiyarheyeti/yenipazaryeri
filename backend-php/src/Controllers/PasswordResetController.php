<?php
namespace App\Controllers;
use App\Database;
use App\Utils\Mailer;

final class PasswordResetController {
  public function request(array $p, array $b): array {
    $email = trim((string)($b['email'] ?? '')); 
    if($email === ''){ 
      http_response_code(422); 
      return ['ok'=>false,'error'=>'validation','fields'=>['email'=>'required']]; 
    }
    
    $pdo = Database::pdo();
    $u = $pdo->prepare("SELECT id FROM users WHERE email=?"); 
    $u->execute([$email]); 
    $uid = $u->fetchColumn();
    
    if(!$uid){ 
      return ['ok'=>true]; // kullanıcı yoksa bile true
    }
    
    $tok = bin2hex(random_bytes(16)); 
    $exp = date('Y-m-d H:i:s', time()+60*60*2);
    
    $pdo->prepare("INSERT INTO password_resets(user_id,token,expires_at) VALUES(?,?,?)")->execute([$uid,$tok,$exp]);
    
    $link = (getenv('APP_BASE_URL') ?: 'http://localhost:3000')."/reset-password?token=$tok";
    Mailer::rawSend($email, "Şifre Sıfırlama", "Şifrenizi sıfırlamak için: $link");
    
    return ['ok'=>true];
  }

  public function reset(array $p, array $b): array {
    $token = $b['token'] ?? ''; 
    $pass = $b['password'] ?? '';
    
    $pdo = Database::pdo();
    $st = $pdo->prepare("SELECT * FROM password_resets WHERE token=? AND used_at IS NULL AND expires_at>NOW()");
    $st->execute([$token]); 
    $row = $st->fetch(); 
    
    if(!$row){ 
      http_response_code(400); 
      return ['ok'=>false,'error'=>'invalid_or_expired']; 
    }
    
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?")->execute([$hash,(int)$row['user_id']]);
    $pdo->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=?")->execute([$row['id']]);
    
    return ['ok'=>true];
  }
}
