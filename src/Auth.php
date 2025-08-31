<?php
namespace App;
class Auth {
  public static function generateToken(int $userId, int $tenantId, string $role): string {
    $payload = [
      'uid'=>$userId,'tid'=>$tenantId,'role'=>$role,'iat'=>time(),'exp'=>time()+86400
    ];
    return self::jwtEncode($payload, Config::JWT_SECRET);
  }
  public static function jwtEncode(array $payload, string $secret): string {
    $header = ['alg'=>'HS256','typ'=>'JWT'];
    $segments = [
      self::b64(json_encode($header)),
      self::b64(json_encode($payload))
    ];
    $signing = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing, $secret, true);
    $segments[] = self::b64($signature);
    return implode('.', $segments);
  }
  private static function b64(string $d): string {
    return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
  }
}
