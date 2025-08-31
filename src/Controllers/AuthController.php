<?php
namespace App\Controllers;

use App\Database;
use App\Auth;
use App\Utils\Totp;

final class AuthController {

  public function login(array $p,array $b): array {
    $email = trim($b['email'] ?? '');
    $pass  = trim($b['password'] ?? '');
    $two   = $b['twofa'] ?? '';

    if ($email === '' || $pass === '') {
      return ['ok'=>false,'error'=>'email and password required'];
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Debug log
    error_log('LOGIN DEBUG DB=' . Database::pdo()->query('SELECT DATABASE()')->fetchColumn());
    error_log('LOGIN DEBUG EMAIL=' . $email);

    // Kullanıcıyı çek
    $st = Database::pdo()->prepare("SELECT id, tenant_id, name, email, role, password_hash, password FROM users WHERE email=?");
    $st->execute([$email]);
    $u = $st->fetch(\PDO::FETCH_ASSOC);
    if (!$u) {
      return ['ok'=>false,'error'=>'invalid credentials'];
    }

    // Şifre kontrolü
    $hash = !empty($u['password_hash']) ? $u['password_hash'] : $u['password'];
    if (!is_string($hash) || $hash === '' || !password_verify($pass, $hash)) {
      error_log('VERIFY=' . (int)password_verify($pass, $hash));
      return ['ok'=>false,'error'=>'invalid credentials'];
    }

    // 2FA kontrolü (geçici devre dışı)
    /*
    if ((int)$u['twofa_enabled'] === 1) {
      if (!$two) {
        $this->bumpLoginAttempt($email,$ip,false);
        return ['ok'=>false,'twofa_required'=>true,'error'=>'2fa_required'];
      }
      if (!Totp::verify($u['twofa_secret'],$two)) {
        $this->bumpLoginAttempt($email,$ip,false);
        return ['ok'=>false,'error'=>'invalid_2fa'];
      }
    }
    */

    // Başarılı giriş
    $token = Auth::generateToken((int)$u['id'], (int)($u['tenant_id'] ?? 1), $u['role'] ?? 'admin');
    $ref   = \App\Controllers\SessionController::issueRefresh($u['id']);

    return [
      'ok' => true,
      'token' => $token,
      'refresh_token' => $ref['token'],
      'refresh_expires_at' => $ref['expires_at'],
      'user' => [
        'id' => $u['id'],
        'tenant_id' => $u['tenant_id'] ?? 1,
        'name' => $u['name'],
        'email' => $u['email'],
        'role' => $u['role'] ?? 'admin',
        'twofa_enabled' => false
      ]
    ];
  }

  // Yeni kayıt endpointi
  public function register(array $p,array $b): array {
    $tenantId = (int)($b['tenant_id'] ?? 0);
    $name     = trim($b['name'] ?? '');
    $email    = trim($b['email'] ?? '');
    $password = $b['password'] ?? '';

    if ($tenantId <= 0 || $name === '' || $email === '' || $password === '') {
      return ['ok'=>false,'error'=>'tenant_id, name, email, password required'];
    }

    $pdo = Database::pdo();
    $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $check->execute([$email]);
    if ($check->fetch()) {
      return ['ok'=>false,'error'=>'email already exists'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare("INSERT INTO users (tenant_id,name,email,password_hash,role) VALUES (?,?,?,?,?)");
    $st->execute([$tenantId,$name,$email,$hash,'user']);
    $id = (int)$pdo->lastInsertId();

    $token = Auth::generateToken($id,$tenantId,'user');
    return ['ok'=>true,'token'=>$token,'user'=>['id'=>$id,'tenant_id'=>$tenantId,'name'=>$name,'email'=>$email,'role'=>'user']];
  }

  // Token doğrulayıp kullanıcıyı döner
  public function me(array $p,array $b): array {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/',$auth,$m)) return ['ok'=>false,'error'=>'no token'];
    $payload = self::decodeToken($m[1]);
    if (!$payload) return ['ok'=>false,'error'=>'invalid token'];

    $st = Database::pdo()->prepare("SELECT id,tenant_id,name,email,role,twofa_enabled FROM users WHERE id=?");
    $st->execute([$payload['uid'] ?? 0]);
    $u = $st->fetch();
    if (!$u) return ['ok'=>false,'error'=>'user not found'];
    return ['ok'=>true,'user'=>$u];
  }

  public function permissions(array $p, array $b, array $q): array {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/Bearer\s+(.+)/',$auth,$m)) return ['ok'=>false,'error'=>'no token'];
    $payload = self::decodeToken($m[1]);
    if (!$payload) return ['ok'=>false,'error'=>'invalid token'];

    $pdo = Database::pdo();
    $st = $pdo->prepare("
      SELECT DISTINCT p.name
      FROM user_roles ur
      JOIN role_permissions rp ON rp.role_id=ur.role_id
      JOIN permissions p ON p.id=rp.permission_id
      WHERE ur.user_id=?
    ");
    $st->execute([$payload['uid'] ?? 0]);
    $permissions = $st->fetchAll(\PDO::FETCH_COLUMN);

    return ['ok'=>true,'items'=>$permissions];
  }

  // Şifre sıfırlama isteği
  public function forgot(array $p,array $b): array {
    $email = trim($b['email'] ?? '');
    if ($email === '') return ['ok'=>true];
    $st = Database::pdo()->prepare("SELECT id FROM users WHERE email=?");
    $st->execute([$email]);
    $uid = $st->fetchColumn();
    if ($uid) {
      $token = bin2hex(random_bytes(32));
      $exp = date('Y-m-d H:i:s', time()+3600);
      $ins = Database::pdo()->prepare("INSERT INTO password_resets (user_id,token,expires_at) VALUES (?,?,?)");
      $ins->execute([(int)$uid,$token,$exp]);
      \App\Utils\Mailer::send($email, "Şifre Sıfırlama", "Token: $token\nLink: https://app.example.com/reset?token=$token");
    }
    return ['ok'=>true];
  }

  // Token ile yeni şifre
  public function reset(array $p,array $b): array {
    $token = $b['token'] ?? '';
    $new   = $b['password'] ?? '';
    if (strlen($new) < 6) return ['ok'=>false,'error'=>'password too short'];
    $st = Database::pdo()->prepare("SELECT * FROM password_resets WHERE token=? AND used_at IS NULL AND expires_at>NOW()");
    $st->execute([$token]);
    $row = $st->fetch();
    if (!$row) return ['ok'=>false,'error'=>'invalid_or_expired'];
    $hash = password_hash($new,PASSWORD_DEFAULT);
    $pdo = Database::pdo();
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash,(int)$row['user_id']]);
    $pdo->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=?")->execute([(int)$row['id']]);
    $pdo->commit();
    return ['ok'=>true];
  }

  // Login attempt kontrolü
  private function bumpLoginAttempt(string $email,string $ip,bool $success): void {
    $pdo = Database::pdo();
    $st = $pdo->prepare("SELECT id,attempts FROM login_attempts WHERE email=? AND ip=?");
    $st->execute([$email,$ip]);
    $row = $st->fetch();
    if ($success) {
      if ($row) {
        $pdo->prepare("UPDATE login_attempts SET attempts=0, locked_until=NULL WHERE id=?")->execute([$row['id']]);
      }
      return;
    }
    $attempts = $row ? (int)$row['attempts']+1 : 1;
    $lockedUntil = null;
    if ($attempts >= 5) { $attempts=0; $lockedUntil = date('Y-m-d H:i:s', time()+15*60); }
    if ($row) {
      $pdo->prepare("UPDATE login_attempts SET attempts=?, locked_until=? WHERE id=?")->execute([$attempts,$lockedUntil,$row['id']]);
    } else {
      $pdo->prepare("INSERT INTO login_attempts (email,ip,attempts,locked_until) VALUES (?,?,?,?)")->execute([$email,$ip,$attempts,$lockedUntil]);
    }
  }

  public function refresh(array $p,array $b): array {
    $rt = $b['refresh_token'] ?? '';
    $new = \App\Controllers\SessionController::rotate($rt);
    if (!$new) { http_response_code(401); return ['ok'=>false,'error'=>'invalid_refresh']; }
    $st = \App\Database::pdo()->prepare("SELECT user_id FROM refresh_tokens WHERE token=?");
    $st->execute([$new['token']]); 
    $uid = (int)$st->fetchColumn(); 
    if (!$uid) { http_response_code(401); return ['ok'=>false,'error'=>'user_not_found']; }
    $jwt = \App\Auth::generateToken($uid, 1, 'admin');
    return ['ok'=>true,'token'=>$jwt,'refresh_token'=>$new['token'],'refresh_expires_at'=>$new['expires_at']];
  }

  public function logoutAll(array $p,array $b): array {
    $uid = $this->userId ?? 0; 
    if ($uid <= 0) { http_response_code(401); return ['ok'=>false,'error'=>'unauthorized']; }
    \App\Controllers\SessionController::revokeAll($uid);
    return ['ok'=>true];
  }

  private static function decodeToken(string $token):?array {
    $parts = explode('.',$token);
    if (count($parts) !== 3) return null;
    [$h,$p,$s] = $parts;
    $sig = hash_hmac('sha256',"$h.$p",\App\Config::JWT_SECRET,true);
    if (!hash_equals($sig,base64_decode(strtr($s,'-_','+/')))) return null;
    return json_decode(base64_decode(strtr($p,'-_','+/')),true);
  }
}
