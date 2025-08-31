<?php
namespace App\Utils;

final class Totp {
  // RFC 6238 - SHA1, 30s period, 6 digits
  public static function verify(string $secret, string $code, int $window=1, int $period=30, int $digits=6): bool {
    $secret = strtoupper($secret);
    $time = floor(time() / $period);
    for ($i=-$window; $i<=$window; $i++) {
      if (self::generate($secret, $time+$i, $period, $digits) === $code) return true;
    }
    return false;
  }

  public static function generate(string $secret, int $timeStep=null, int $period=30, int $digits=6): string {
    if($timeStep===null) $timeStep = floor(time()/$period);
    $key = self::base32Decode($secret);
    $binTime = pack('N*', 0) . pack('N*', $timeStep);
    $hash = hash_hmac('sha1', $binTime, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $trunc = (ord($hash[$offset]) & 0x7F) << 24 |
             (ord($hash[$offset+1]) & 0xFF) << 16 |
             (ord($hash[$offset+2]) & 0xFF) << 8 |
             (ord($hash[$offset+3]) & 0xFF);
    $code = $trunc % (10 ** $digits);
    return str_pad((string)$code, $digits, '0', STR_PAD_LEFT);
  }

  public static function randomSecret(int $len=16): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $s=''; for($i=0;$i<$len;$i++){ $s.=$alphabet[random_int(0,strlen($alphabet)-1)]; }
    return $s;
  }

  private static function base32Decode(string $b32): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = rtrim($b32, '=');
    $bits=''; $result='';
    for ($i=0;$i<strlen($b32);$i++) {
      $val = strpos($alphabet, $b32[$i]);
      if($val===false) continue;
      $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
    }
    for ($i=0; $i+8 <= strlen($bits); $i+=8) {
      $result .= chr(bindec(substr($bits, $i, 8)));
    }
    return $result;
  }
}

