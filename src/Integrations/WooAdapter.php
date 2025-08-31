<?php
namespace App\Integrations;

use App\Utils\Http;
use App\Utils\Rate;

final class WooAdapter {
  private string $base;
  private array $auth;
  private array $headers;

  public function __construct(array $connection) {
    // ✅ base_url olarak woo_site_url varsa onu, yoksa store_url'ü al
    $this->base = rtrim($connection['woo_site_url'] ?? $connection['store_url'] ?? '', '/');
    if (!str_contains($this->base, '/wp-json/wc/v3')) {
      $this->base .= '/wp-json/wc/v3';
    }

    // ✅ API anahtarlarını doğru kolondan oku
    $ck = $connection['woo_consumer_key'] ?? $connection['consumer_key'] ?? '';
    $cs = $connection['woo_consumer_secret'] ?? $connection['consumer_secret'] ?? '';

    $this->auth = [
      'consumer_key' => $ck,
      'consumer_secret' => $cs
    ];

    $this->headers = ['Accept' => 'application/json'];
  }

  private function qs(array $params = []): string {
    $all = array_merge($this->auth, $params);
    return '?' . http_build_query($all);
  }

  private function headers(): array {
    return $this->headers;
  }

  /** 🔹 WooCommerce ürünlerini listele (tüm sayfaları döner) */
  public function listProducts(int $perPage=100): array {
    $all = [];
    $page = 1;
    $attempt = 0;

    do {
      $url = $this->base.'/products'.$this->qs([
        'per_page'=>$perPage,
        'page'=>$page,
        'status'=>'any' // ✅ tüm statüler
      ]);

      [$code, $data, $err] = Http::json('GET', $url, $this->headers());

      if ($code === 429 || $code >= 500) {
        sleep(Rate::backoff($attempt++));
        continue;
      }

      if ($code >= 200 && $code < 300 && is_array($data)) {
        foreach ($data as $product) {
          $all[] = [
            'id'            => $product['id'],
            'name'          => $product['name'] ?? '',
            'description'   => $product['description'] ?? '',
            'price'         => $product['price'] ?? 0,
            'regular_price' => $product['regular_price'] ?? 0,
            'sale_price'    => $product['sale_price'] ?? 0,
            'stock'         => $product['stock_quantity'] ?? 0,
            'sku'           => $product['sku'] ?? '',
            'status'        => $product['status'] ?? 'draft',
            'categories'    => $product['categories'] ?? [],
            'images'        => $product['images'] ?? []
          ];
        }

        if (count($data) < $perPage) break; // son sayfa
        $page++;
      } else {
        break;
      }
    } while (true);

    return $all;
  }
}
