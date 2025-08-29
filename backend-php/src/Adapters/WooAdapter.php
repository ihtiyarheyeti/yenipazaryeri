<?php
namespace App\Adapters;

final class WooAdapter {
  // ProductDTO -> WooCommerce payload
  public static function toPayload(array $dto): array {
    return [
      'id'=>$dto['id']??null,
      'name'=>$dto['name'],
      'sku'=>$dto['sku']??'',
      'regular_price'=>(string)($dto['price']??0),
      'stock_quantity'=>$dto['stock']??0,
      'categories'=>isset($dto['category_woo_id']) ? [['id'=>(int)$dto['category_woo_id']]] : array_map(fn($c)=>['name'=>$c], $dto['category_path']??[]),
      'attributes'=>self::mapAttributes($dto['attrs']??[])
    ];
  }

  private static function mapAttributes(array $attrs): array {
    $out=[];
    foreach($attrs as $name=>$value){
      $out[]=['name'=>$name,'options'=>[$value]];
    }
    return $out;
  }
}
