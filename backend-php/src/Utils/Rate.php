<?php
namespace App\Utils;

final class Rate {
  public static function backoff(int $attempt): int {
    $base = 2 ** min($attempt, 6); // 1,2,4,8,16,32,64
    return min(60, $base); // en çok 60 sn
  }
}
