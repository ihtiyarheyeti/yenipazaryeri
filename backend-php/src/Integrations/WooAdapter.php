<?php namespace App\Integrations;

use App\Utils\Http;
use App\Utils\Rate;

final class WooAdapter {
  private string $base;
  private array $auth;
  private array $headers;

  public function __construct(array $connection) {
    $this->base = rtrim($connection['base_url'], '/');
    $this->auth = ['consumer_key' => $connection['api_key'], 'consumer_secret' => $connection['api_secret']];
    $this->headers = ['Accept' => 'application/json'];
  }

  private function qs(array $params = []): string {
    $all = array_merge($this->auth, $params);
    return '?' . http_build_query($all);
  }

  private function headers(): array {
    return $this->headers;
  }

  /** Kategorileri listele */
  public function listCategories(int $page = 1, int $perPage = 100): array {
    $all = [];
    $page = 1;
    do {
      [$code, $data, $err] = Http::json('GET', $this->base.'/products/categories'.$this->qs(['page'=>$page,'per_page'=>$perPage]), ['Accept'=>'application/json']);
      if($code>=200 && $code<300 && is_array($data)){
        $batch=$data;
        foreach($batch as $c){
          $all[]=[
            'id'=>(string)$c['id'],
            'parent'=> isset($c['parent']) && $c['parent'] ? (string)$c['parent'] : null,
            'name'=>$c['name']??'',
            'path'=>$c['name']??''
          ];
        }
        if(count($batch)<$perPage) break;
        $page++;
      } else { break; }
    } while(true);
    return $all;
  }

  public function getProductBySku(string $sku){
    $url=$this->base.'/products'.$this->qs(['sku'=>$sku]);
    [$code,$data]=Http::json('GET',$url);
    if($code>=200 && $code<300 && is_array($data) && !empty($data)) return $data[0];
    return null;
  }

  /** Ürünü güncelle (ör: images veya basic fields) */
  public function updateProduct(string $productId, array $payload): array {
    $attempt=0;
    do{
      [$code,$data,$err]=Http::json('PUT', $this->base.'/products/'.$productId.$this->qs(), ['Accept'=>'application/json'], $payload, 40);
      if($code===429 || $code>=500){ sleep(Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200 && $code<300, 'code'=>$code, 'data'=>$data, 'err'=>$err];
    }while($attempt<6);
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** Varyant güncelle (stok/fiyat) */
  public function updateVariation(string $productId, string $variationId, array $payload): array {
    $attempt=0;
    do{
      [$code,$data,$err]=Http::json('PUT', $this->base."/products/{$productId}/variations/{$variationId}".$this->qs(), [], $payload, 40);
      if($code===429 || $code>=500){ sleep(Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200 && $code<300,'code'=>$code,'data'=>$data,'err'=>$err];
    }while($attempt<6);
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** Sipariş oluştur */
  public function createOrder(array $payload): array {
    $attempt=0;
    do{
      [$code,$data,$err] = \App\Utils\Http::json('POST', $this->base.'/orders'.$this->qs(), ['Accept'=>'application/json'], $payload, 60);
      if($code===429 || $code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200&&$code<300, 'code'=>$code, 'data'=>$data, 'err'=>$err];
    }while($attempt<6);
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** SKU ile ürün bul (search query ile) */
  public function findProductBySku(string $sku) {
    // Woo özel endpoint yoksa search query paramı kullanan eklentiler var; basit list ile geçiyoruz:
    [$code,$data,$err] = \App\Utils\Http::json('GET', $this->base.'/products'.$this->qs(['search'=>$sku,'per_page'=>20]), ['Accept'=>'application/json']);
    return ($code>=200&&$code<300) ? $data : [];
  }

  /** SKU ile varyant bul */
  public function findVariationBySku(string $productId, string $sku) {
    $vars = $this->listVariations($productId, 1, 100);
    foreach($vars as $v) { 
      if(($v['sku']??'') === $sku) return $v; 
    }
    return null;
  }

  /** İade oluştur */
  public function createRefund(string $orderId, array $payload): array {
    // payload: { amount, reason, refund_payment: true, line_items: [{id|sku|product_id, quantity, refund_total}] }
    $attempt=0;
    do{
      [$code,$data,$err] = \App\Utils\Http::json('POST', $this->base."/orders/{$orderId}/refunds".$this->qs(), ['Accept'=>'application/json'], $payload, 60);
      if($code===429 || $code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200&&$code<300, 'code'=>$code, 'data'=>$data, 'err'=>$err];
    }while($attempt<6);
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** Ürün listesi çek */
  public function listProducts(int $page=1, int $perPage=100): array {
    $all = [];
    $attempt = 0;
    
    do {
      $url = $this->base.'/products?per_page='.$perPage.'&page='.$page.'&status=publish';
      
      [$code, $data, $err] = Http::json('GET', $url, $this->headers());
      
      if ($code === 429 || $code >= 500) {
        sleep(Rate::backoff($attempt++));
        continue;
      }
      
      if ($code >= 200 && $code < 300 && is_array($data)) {
        $batch = $data;
        foreach ($batch as $product) {
          $all[] = [
            'id' => $product['id'],
            'name' => $product['name'] ?? '',
            'title' => $product['name'] ?? '',
            'description' => $product['description'] ?? '',
            'price' => $product['price'] ?? 0,
            'regular_price' => $product['regular_price'] ?? 0,
            'sale_price' => $product['sale_price'] ?? 0,
            'stock' => $product['stock_quantity'] ?? 0,
            'stock_quantity' => $product['stock_quantity'] ?? 0,
            'sku' => $product['sku'] ?? '',
            'status' => $product['status'] ?? 'publish',
            'categories' => $product['categories'] ?? [],
            'images' => $product['images'] ?? [],
            'weight' => $product['weight'] ?? '',
            'dimensions' => $product['dimensions'] ?? [],
            'tax_status' => $product['tax_status'] ?? 'taxable',
            'tax_class' => $product['tax_class'] ?? ''
          ];
        }
        
        if (count($batch) < $perPage) break;
        $page++;
      } else {
        break;
      }
      
    } while (true);
    
    return $all;
  }

  /** Varyantları listele */
  public function listVariations(string $productId, int $page=1, int $perPage=100): array {
    $attempt=0; $all=[]; do{
      $url=$this->base."/products/{$productId}/variations".$this->qs(['page'=>$page,'per_page'=>$perPage]);
      [$code,$data,$err]=\App\Utils\Http::json('GET',$url,['Accept'=>'application/json'],null,40);
      if($code===429||$code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      if($code>=200&&$code<300 && is_array($data)){ $all=array_merge($all,$data); if(count($data)<$perPage) break; $page++; } else break;
    }while(true);
    return $all;
  }

  /** Varyant oluştur */
  public function createVariation(string $productId, array $payload): array {
    $attempt=0;
    do{
      [$code,$data,$err]=\App\Utils\Http::json('POST', $this->base."/products/{$productId}/variations".$this->qs(), ['Accept'=>'application/json'], $payload, 60);
      if($code===429||$code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200&&$code<300,'code'=>$code,'data'=>$data,'err'=>$err];
    }while(true);
  }

  /** Ürün getir */
  public function getProduct(string $id){
    [$code,$data,$err]=\App\Utils\Http::json('GET', $this->base.'/products/'.$id.$this->qs(), ['Accept'=>'application/json']);
    return ($code>=200&&$code<300)? $data : null;
  }

  /** Ürün görsellerini listele */
  public function listProductImages(string $productId): array {
    [$code,$data,$err]=\App\Utils\Http::json('GET', $this->base."/products/{$productId}/images".$this->qs(), ['Accept'=>'application/json'], null, 40);
    return ($code>=200&&$code<300 && is_array($data)) ? $data : [];
  }

  /** Siparişleri listele */
  public function listOrders(int $page=1, int $perPage=50, ?string $afterIso=null): array {
    $q=['page'=>$page,'per_page'=>$perPage,'status'=>'any'];
    if($afterIso) $q['after']=$afterIso;
    [$code,$data,$err]=\App\Utils\Http::json('GET', $this->base.'/orders'.$this->qs($q), ['Accept'=>'application/json'], null, 60);
    return ['ok'=>$code>=200&&$code<300,'code'=>$code,'data'=>is_array($data)?$data:[],'err'=>$err];
  }

  /** Sipariş durumunu güncelle */
  public function updateOrderStatus(string $orderId, string $status): array {
    [$code,$data,$err]=\App\Utils\Http::json('PUT', $this->base.'/orders/'.$orderId.$this->qs(), ['Accept'=>'application/json'], ['status'=>$status], 40);
    return ['ok'=>$code>=200&&$code<300,'code'=>$code,'data'=>$data,'err'=>$err];
  }
}
