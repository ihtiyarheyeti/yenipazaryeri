<?php
namespace App\Standardizer;

final class CanonicalProduct {
  public string $name;
  public ?string $brand = null;
  public ?string $description = null;
  public ?string $category_external = null; // kaynağın categoryId/path
  public array $variants = [];               // [ ['sku'=>?, 'price'=>?, 'stock'=>?, 'attrs'=>[]], ... ]
  public string $origin;                     // 'woo'|'trendyol'
  public string $origin_id;                  // kaynak product id
}

