<?php
namespace App\Controllers;
use App\Database;

final class SessionController {
  // login sonrası refresh token üret ve kaydet
  public static function issueRefresh(int $userId): array {
    $tok = bin2hex(random_bytes(32));
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $exp = date('Y-m-d H:i:s', time() + 60*60*24*15); // 15 gün
    $pdo = Database::pdo();
    $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, user_agent, ip, expires_at) VALUES (?,?,?,?,?)")
        ->execute([$userId,$tok,$ua,$ip,$exp]);
    return ['token'=>$tok, 'expires_at'=>$exp];
  }

  public static function rotate(string $token): ?array {
    $pdo=Database::pdo();
    $st=$pdo->prepare("SELECT * FROM refresh_tokens WHERE token=? AND revoked_at IS NULL AND expires_at>NOW()");
    $st->execute([$token]); $row=$st->fetch();
    if(!$row) return null;
    // eskiyi iptal et, yenisini ver
    $pdo->prepare("UPDATE refresh_tokens SET revoked_at=NOW() WHERE id=?")->execute([$row['id']]);
    return self::issueRefresh((int)$row['user_id']);
  }

  public static function revokeAll(int $userId): void {
    Database::pdo()->prepare("UPDATE refresh_tokens SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$userId]);
  }
}
