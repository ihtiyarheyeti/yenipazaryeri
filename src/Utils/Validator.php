<?php
namespace App\Utils;

final class Validator {
  public static function require(array $body, array $fields): array {
    $err=[];
    foreach($fields as $f){ 
      if(!isset($body[$f]) || $body[$f]==='') 
        $err[$f]='required'; 
    }
    return $err;
  }
  
  public static function minlen(?string $v, int $n): bool { 
    return $v!==null && mb_strlen(trim($v))>=$n; 
  }
  
  public static function email(?string $v): bool {
    return $v!==null && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
  }
  
  public static function numeric($v): bool {
    return is_numeric($v);
  }
  
  public static function positive($v): bool {
    return is_numeric($v) && $v > 0;
  }
}
