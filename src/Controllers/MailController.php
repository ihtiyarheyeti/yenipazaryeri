<?php
namespace App\Controllers;
use App\Utils\Mailer;

final class MailController {
  public function test(array $p, array $b, array $q): array {
    $to = trim($q['to'] ?? '');
    if (!$to) return ['ok' => false, 'error' => 'to required'];
    
    $ok = Mailer::send($to, 'SMTP Test', 'Bu bir test e-postasıdır.');
    return ['ok' => $ok];
  }
}

