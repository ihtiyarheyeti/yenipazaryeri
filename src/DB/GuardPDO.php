<?php
namespace App\DB;

final class GuardPDO {
  public static function guardQuery(string $sql): bool {
    if (getenv('DB_GUARD')!=='on') return true;
    $s=strtolower(preg_replace('/\s+/', ' ', $sql));
    foreach([' drop ',' truncate ',' alter '] as $k){ 
      if(strpos($s,$k)!==false) return false; 
    }
    return true;
  }
  
  public static function execute(\PDO $pdo, string $sql, array $params = []): \PDOStatement {
    if (!self::guardQuery($sql)) {
      throw new \Exception('Dangerous SQL operation blocked by DB Guard');
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
  }
}
