<?php
namespace App\Controllers;
use App\Standardizer\CanonicalProduct;
use App\Standardizer\Standardizer;

final class StdController {
  /** POST /standardize  body:{ origin:'woo'|'trendyol', origin_id:'...', product:{...}, variants:[...] } */
  public function standardize(array $p, array $b): array {
    $tenant=\App\Context::$tenantId;
    $cp = new CanonicalProduct();
    $cp->origin = $b['origin'];
    $cp->origin_id = (string)$b['origin_id'];
    $cp->name = $b['product']['name'] ?? '';
    $cp->brand = $b['product']['brand'] ?? null;
    $cp->description = $b['product']['description'] ?? null;
    $cp->category_external = $b['product']['category_external'] ?? null;
    $cp->variants = $b['variants'] ?? [];
    return Standardizer::upsert($cp,$tenant);
  }
}

