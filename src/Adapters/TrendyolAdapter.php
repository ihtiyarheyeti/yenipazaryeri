<?php
namespace App\Adapters;

final class TrendyolAdapter {
  // ProductDTO -> Trendyol payload
  public static function toPayload(array $dto): array {
    return [
      'supplierId' => $dto['supplier_id'] ?? null,
      'items' => [[
        'productMainId' => $dto['id'],
        'barcode' => $dto['sku'] ?? null,
        'title' => $dto['name'],
        'brand' => $dto['brand'] ?? '',
        'categoryId' => $dto['category_trendyol_id'] ?? null,
        'attributes' => self::mapAttributes($dto['attrs'] ?? []),
        'stockCode' => $dto['sku'],
        'quantity' => $dto['stock'],
        'dimensionalWeight' => 1,
        'listPrice' => $dto['price'],
        'salePrice' => $dto['price'],
      ]]
    ];
  }

  private static function mapAttributes(array $attrs): array {
    $out=[];
    foreach($attrs as $k=>$v){
      $out[]=['attributeId'=>$k,'attributeValue'=>$v];
    }
    return $out;
  }
}
