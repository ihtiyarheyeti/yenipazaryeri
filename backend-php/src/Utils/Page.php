<?php
namespace App\Utils;

final class Page {
  public static function paginate(array $items, int $page=1, int $size=20): array {
    $total = count($items);
    $start = max(0, ($page-1)*$size);
    return ['items'=>array_slice($items,$start,$size),'total'=>$total,'page'=>$page,'size'=>$size];
  }
}
