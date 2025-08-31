<?php
namespace App\Controllers;
use App\Database;
use App\Utils\Totp;

final class TwoFAController {
  // 2FA kurulum başlat: secret üret, kullanıcıya kaydet (enabled=0), otpauth uri döndür
  public function setup(array $p, array $b, array $q): array {
    $userId = (int)($b['user_id'] ?? 0);
    if($userId <= 0) return ['ok' => false, 'error' => 'user_id required'];
    $secret = Totp::randomSecret(16);
    $pdo = Database::pdo();
    $pdo->prepare("UPDATE users SET twofa_secret=?, twofa_enabled=0 WHERE id=?")->execute([$secret, $userId]);
    $issuer = urlencode('Yenipazaryeri');
    $label = urlencode('user'.$userId);
    $uri = "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    return ['ok' => true, 'secret' => $secret, 'otpauth_uri' => $uri];
  }

  // 2FA etkinleştir: girilen kodu doğrula
  public function enable(array $p, array $b): array {
    $userId = (int)($b['user_id'] ?? 0);
    $code = trim($b['code'] ?? '');
    if($userId <= 0 || $code === '') return ['ok' => false, 'error' => 'user_id and code required'];
    $st = Database::pdo()->prepare("SELECT twofa_secret FROM users WHERE id=?");
    $st->execute([$userId]);
    $secret = $st->fetchColumn();
    if(!$secret) return ['ok' => false, 'error' => 'no secret'];
    if(!\App\Utils\Totp::verify($secret, $code)) return ['ok' => false, 'error' => 'invalid_2fa_code'];
    Database::pdo()->prepare("UPDATE users SET twofa_enabled=1 WHERE id=?")->execute([$userId]);
    return ['ok' => true];
  }

  // 2FA devre dışı: (isteğe göre şifre/kod isteyebilirsin; burada sadece code kontrolü yapıyoruz)
  public function disable(array $p, array $b): array {
    $userId = (int)($b['user_id'] ?? 0);
    $code = trim($b['code'] ?? '');
    if($userId <= 0 || $code === '') return ['ok' => false, 'error' => 'user_id and code required'];
    $st = Database::pdo()->prepare("SELECT twofa_secret FROM users WHERE id=?");
    $st->execute([$userId]);
    $secret = $st->fetchColumn();
    if(!$secret) return ['ok' => false, 'error' => 'no secret'];
    if(!\App\Utils\Totp::verify($secret, $code)) return ['ok' => false, 'error' => 'invalid_2fa_code'];
    Database::pdo()->prepare("UPDATE users SET twofa_enabled=0 WHERE id=?")->execute([$userId]);
    return ['ok' => true];
  }

  public function status(array $p, array $b, array $q): array {
    $userId = (int)($q['user_id'] ?? 0);
    $st = Database::pdo()->prepare("SELECT twofa_enabled FROM users WHERE id=?");
    $st->execute([$userId]);
    $v = $st->fetchColumn();
    return ['ok' => true, 'twofa_enabled' => (int)$v === 1];
  }
}

