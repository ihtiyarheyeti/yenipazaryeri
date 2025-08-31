<?php
namespace App\Utils;
use App\Context;

final class JsonLogger {
  public static function event(string $level, string $message, array $fields=[]): void {
    $line=[
      '@timestamp'=>date('c'),
      'level'=>$level,
      'message'=>$message,
      'service'=>'yenipazaryeri',
      'tenant_id'=>Context::$tenantId,
      'fields'=>$fields
    ];
    $dir=__DIR__.'/../../storage/logs'; if(!is_dir($dir)) @mkdir($dir,0777,true);
    file_put_contents($dir.'/app.jsonl', json_encode($line, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
  }
}
