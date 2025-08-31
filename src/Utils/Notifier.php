<?php
namespace App\Utils;
use App\Database;

final class Notifier {
  public static function notify(int $tenant, ?int $uid, string $title, string $body, string $channel='inapp', ?string $url=null){
    $pdo=Database::pdo();
    $pdo->prepare("INSERT INTO notifications(tenant_id,user_id,channel,title,body,url) VALUES (?,?,?,?,?,?)")
        ->execute([$tenant,$uid,$channel,$title,$body,$url]);
    
    if($channel==='email' && $uid){
      // email adresini users tablosundan çek
      $st=$pdo->prepare("SELECT email FROM users WHERE id=?"); 
      $st->execute([$uid]); 
      $mail=$st->fetchColumn();
      if($mail) {
        // Mailer sınıfı varsa kullan
        if(class_exists('\App\Utils\Mailer')) {
          \App\Utils\Mailer::rawSend($mail,$title,$body);
        }
      }
    }
    
    if($channel==='webhook'){
      $st=$pdo->prepare("SELECT target_url FROM tenant_webhooks WHERE tenant_id=? AND event=?");
      $st->execute([$tenant,$title]); 
      foreach($st->fetchAll() as $r){
        @file_get_contents($r['target_url'].'?msg='.urlencode($body));
      }
    }
  }
}
