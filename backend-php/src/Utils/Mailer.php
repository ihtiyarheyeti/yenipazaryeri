<?php
namespace App\Utils;

final class Mailer {
  public static function rawSend(string $to, string $subject, string $body): bool {
    // PROD: SMTP ile değiştir. Geliştirme için file log:
    $logDir = __DIR__.'/../../storage';
    if (!is_dir($logDir)) {
      mkdir($logDir, 0755, true);
    }
    file_put_contents($logDir.'/mails.log', "[".date('c')."] $to | $subject\n$body\n\n", FILE_APPEND);
    return true;
  }
}

