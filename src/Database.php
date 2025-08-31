<?php
namespace App;
use PDO, PDOException;

final class Database {
  private static ?PDO $pdo = null;

  public static function pdo(): PDO {
    if (self::$pdo) return self::$pdo;

    $dsn = 'mysql:host=' . Config::DB_HOST .
           ';port=' . Config::DB_PORT .
           ';dbname=' . Config::DB_NAME .
           ';charset=' . Config::DB_CHARSET;

    try {
      self::$pdo = new PDO(
        $dsn,
        Config::DB_USER,
        Config::DB_PASS,
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
      );
      return self::$pdo;
    } catch (PDOException $e) {
  http_response_code(500);
  echo json_encode([
    'ok'     => false,
    'error'  => 'DB connection failed',
    'detail' => $e->getMessage(),   // zaten var
    'dsn'    => $dsn,               // debug için eklendi
    'user'   => Config::DB_USER     // debug için eklendi
  ]);
  exit;
}
  }
}
