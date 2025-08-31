<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Config;

$cfg = Config::queue();
$limit = $cfg['batch_limit'];
$interval = (int)(getenv('WORKER_INTERVAL') ?: 3); // saniye

echo "[worker] starting... limit={$limit} interval={$interval}s\n";

while (true) {
  try {
    $pdo = Database::pdo();
    // ready jobs
    $st = $pdo->prepare("SELECT * FROM jobs WHERE status='pending' AND (next_attempt_at IS NULL OR next_attempt_at<=NOW()) ORDER BY id ASC LIMIT ?");
    $st->execute([$limit]);
    $jobs = $st->fetchAll();

    if (!$jobs) { sleep($interval); continue; }

    foreach($jobs as $j){
      $pdo->prepare("UPDATE jobs SET status='running' WHERE id=?")->execute([$j['id']]);
      $ok = true; $msg='ok';

      // Burada HTTP controllerdaki QueueController::process ile aynı switch'i kullanmak isterseniz 
      // o sınıfı include edip methodlarını çağırabilirsiniz. Basitlik için process endpoint'ini local çağırmayın.
      // Örnek olarak küçük bir dispatcher yazılabilir veya mevcut controller require edilip new ile kullanılabilir.
      // Kısa tutmak adına burada retry mekanizmasını tekrar etmiyoruz; process endpoint'i ile aynı mantığı
      // paylaşmak en doğrusu.

      // En basit entegrasyon: HTTP process'i tetikleyelim (aynı kodu kullanır)
      // Eğer http yoksa doğrudan QueueController sınıfını import edip process çalıştır.
      // Örnek:
      $qc = new \App\Controllers\QueueController();
      $qc->process([],[],['limit'=>1]); // job başına işlem

      // Döngü hızlı dönmesin:
      usleep(200000);
    }

  } catch (\Throwable $e) {
    fwrite(STDERR, "[worker] error: ".$e->getMessage()."\n");
    sleep(max(1,$interval));
  }
}
