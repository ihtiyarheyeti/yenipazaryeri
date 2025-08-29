<?php
namespace App\Integrations;
use App\Utils\Http;
use App\Utils\Rate;

/**
 * Not: Gerçek endpoint path'leri connection.base_url ve supplier_id'ye göre ayarlanır.
 * Örn base_url: https://api.trendyol.com/sapigw
 * Auth: Header "Authorization: Basic base64(api_key:api_secret)" veya "Username/Password" benzeri — connection'dan üret.
 */
final class TrendyolAdapter {
  private string $base;
  private string $authBasic;
  private string $supplier;
  private string $apiKey;
  private string $apiSecret;

  public function __construct(array $conn){
    // Trendyol'un doğru API endpoint'i (dokümantasyona göre)
    $this->base = 'https://api.trendyol.com/sapigw';
    
    $key=$conn['api_key']??''; $sec=$conn['api_secret']??'';
    
    // Trendyol dokümantasyonuna göre: Basic base64(API_KEY:API_SECRET)
    $this->authBasic='Basic '.base64_encode($key.':'.$sec);
    
    $this->supplier=$conn['supplier_id']??'';
    $this->apiKey=$key;
    $this->apiSecret=$sec;
  }
  
  private function getApiKey(): string {
    return $this->apiKey ?? '';
  }
  
  private function getApiSecret(): string {
    return $this->apiSecret ?? '';
  }
  
  private function headers(): array {
    // Trendyol dokümantasyonuna göre gerekli header'lar
    return [
      'Accept'=>'application/json',
      'Authorization'=>$this->authBasic,
      'Content-Type'=>'application/json',
      'User-Agent'=>'YeniPazarYeri-SelfIntegration/1.0'
    ];
  }

  /** Category/Taxonomy sayfalı çek (örnek iskelet) */
  public function listTaxonomy(): array {
    $all=[]; $page=0; $size=200; $attempt=0;
    do{
      $url=$this->base."/product-categories?page=$page&size=$size";
      [$code,$data,$err]=Http::json('GET',$url,$this->headers(),null,40);
      if($code===429 || $code>=500){ sleep(Rate::backoff($attempt++)); continue; }
      if($code>=200 && $code<300 && is_array($data)){
        $items=$data['categories'] ?? $data; // API yapına göre
        foreach($items as $c){
          $all[]=[
            'id'=>(string)($c['id'] ?? $c['categoryId'] ?? ''),
            'parent'=> isset($c['parentId']) ? (string)$c['parentId'] : null,
            'name'=>$c['name'] ?? '',
            'path'=>$c['hierarchy'] ?? ($c['name'] ?? '')
          ];
        }
        if(count($items)<$size) break;
        $page++;
      } else { break; }
    }while(true);
    return $all;
  }

  /** Fiyat/Stok liste (polling) — örnek: son X değişiklikleri çek */
  public function listPriceInventory(?string $modifiedSince=null, int $page=0, int $size=200): array {
    $qs=['supplierId'=>$this->supplier, 'page'=>$page, 'size'=>$size];
    if($modifiedSince){ $qs['modifiedStartDate']=$modifiedSince; }
    $url=$this->base.'/suppliers/'.$this->supplier.'/products?'.http_build_query($qs);
    $attempt=0;
    do{
      [$code,$data,$err]=Http::json('GET',$url,$this->headers(),null,60);
      if($code===429 || $code>=500){ sleep(Rate::backoff($attempt++)); continue; }
      if($code>=200 && $code<300 && is_array($data)){
        return ['ok'=>true,'data'=>$data];
      }
      return ['ok'=>false,'code'=>$code,'err'=>$err,'data'=>$data];
    }while($attempt<6);
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** Fiyat/Stok güncelle (örnek payload iskeleti) */
  public function updateStockPrice(array $items): array {
    // $items: [['barcode'=>'SKU-1','quantity'=>5,'listPrice'=>199.9], ...] – gerçek alanları senin eşlemene göre doldur.
    $url=$this->base.'/suppliers/'.$this->supplier.'/products/price-and-inventory';
    $attempt=0;
    do{
      [$code,$data,$err]=Http::json('POST',$url,$this->headers(),['items'=>$items],60);
      if($code===429 || $code>=500){ sleep(Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200 && $code<300,'code'=>$code,'data'=>$data,'err'=>$err];
    }while($attempt<6);
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** Sipariş oluştur (DEMO/STUB) */
  public function createOrder(array $payload): array {
    // NOT: Gerçek Trendyol API'si sipariş oluşturmayı desteklemez; bu metod demo/test içindir.
    // Biz local mapping & log için success döndürüp external_id üretiyoruz.
    return ['ok'=>true,'code'=>201,'data'=>['id'=>'TY-'.time(), 'echo'=>$payload], 'err'=>null];
  }

  /** Siparişleri listele - tarih filtreli */
  public function listOrders(string $startIso, string $endIso, int $page=0, int $size=200): array {
    $url=$this->base."/suppliers/{$this->supplier}/orders?".http_build_query(['startDate'=>$startIso,'endDate'=>$endIso,'page'=>$page,'size'=>$size]);
    $attempt=0; 
    do{
      [$code,$data,$err]=\App\Utils\Http::json('GET',$url,$this->headers(),null,40);
      if($code===429||$code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      if($code>=200&&$code<300 && is_array($data)){
        return ['ok'=>true,'data'=>$data];
      }
      return ['ok'=>false,'code'=>$code,'err'=>$err,'data'=>$data];
    }while($attempt<6); 
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** Siparişleri listele - basit */
  public function listOrdersSimple(int $page=0, int $size=200, ?string $modifiedSinceIso=null): array {
    $qs=['page'=>$page,'size'=>$size];
    if($modifiedSinceIso) $qs['orderDateStart']=$modifiedSinceIso;
    $url=$this->base.'/suppliers/'.$this->supplier.'/orders?'.http_build_query($qs);
    $attempt=0;
    do{
      [$code,$data,$err]=\App\Utils\Http::json('GET',$url,$this->headers(),null,60);
      if($code===429||$code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      if($code>=200&&$code<300 && is_array($data)){
        return ['ok'=>true,'data'=>$data];
      }
      return ['ok'=>false,'code'=>$code,'err'=>$err,'data'=>$data];
    }while($attempt<6);
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** İade listesi - sayfalı, tarih filtreli */
  public function listReturns(string $startIso, string $endIso, int $page=0, int $size=200): array {
    $url=$this->base."/suppliers/{$this->supplier}/returns?".http_build_query(['startDate'=>$startIso,'endDate'=>$endIso,'page'=>$page,'size'=>$size]);
    $attempt=0; 
    do{
      [$code,$data,$err]=\App\Utils\Http::json('GET',$url,$this->headers(),null,40);
      if($code===429||$code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200&&$code<300,'code'=>$code,'data'=>$data,'err'=>$err];
    }while($attempt<6); 
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** İade işlemi - kabul/ret */
  public function actOnReturn(string $externalId, string $action, ?string $note=null): array {
    // action: accept|reject
    $url=$this->base."/suppliers/{$this->supplier}/returns/{$externalId}";
    $payload=['action'=>$action,'note'=>$note];
    $attempt=0; 
    do{
      [$code,$data,$err]=\App\Utils\Http::json('POST',$url,$this->headers(),$payload,40);
      if($code===429||$code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200&&$code<300,'code'=>$code,'data'=>$data,'err'=>$err];
    }while($attempt<6); 
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** İptal listesi - sayfalı, tarih filtreli */
  public function listCancellations(string $startIso, string $endIso, int $page=0, int $size=200): array {
    $url=$this->base."/suppliers/{$this->supplier}/cancellations?".http_build_query(['startDate'=>$startIso,'endDate'=>$endIso,'page'=>$page,'size'=>$size]);
    $attempt=0; 
    do{
      [$code,$data,$err]=\App\Utils\Http::json('GET',$url,$this->headers(),null,40);
      if($code===429||$code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200&&$code<300,'code'=>$code,'data'=>$data,'err'=>$err];
    }while($attempt<6); 
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** İptal onaylama */
  public function approveCancel(string $externalId, ?string $note=null): array {
    $url=$this->base."/suppliers/{$this->supplier}/cancellations/{$externalId}/approve";
    $attempt=0; 
    do{
      [$code,$data,$err]=\App\Utils\Http::json('POST',$url,$this->headers(),['note'=>$note],40);
      if($code===429||$code>=500){ sleep(\App\Utils\Rate::backoff($attempt++)); continue; }
      return ['ok'=>$code>=200&&$code<300,'code'=>$code,'data'=>$data,'err'=>$err];
    }while($attempt<6); 
    return ['ok'=>false,'code'=>0,'err'=>'retry_exceeded'];
  }

  /** Ürün oluştur/güncelle */
  public function createOrUpdateProducts(array $items): array {
    // items: Trendyol ürün payload'ları (şablon aşağıda builder ile üretilecek)
    $url = $this->base.'/suppliers/'.$this->supplier.'/v2/products';
    $attempt = 0;
    do {
      [$code, $data, $err] = \App\Utils\Http::json('POST', $url, $this->headers(), ['items' => $items], 90);
      if ($code === 429 || $code >= 500) { 
        sleep(\App\Utils\Rate::backoff($attempt++)); 
        continue; 
      }
      return ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'data' => $data, 'err' => $err];
    } while ($attempt < 6);
    return ['ok' => false, 'code' => 0, 'err' => 'retry_exceeded'];
  }

  /** Ürün görsellerini yükle */
  public function uploadImages(string $productMainId, array $urls): array {
    // TY media API: ürün görselleri için POST payload { "productMainId": "...", "images": ["url1","url2",...] }
    $url=$this->base.'/suppliers/'.$this->supplier.'/v2/products/images';
    [$code,$data,$err]=\App\Utils\Http::json('POST',$url,$this->headers(),[
      'productMainId'=>$productMainId,
      'images'=>$urls
    ], 90);
    return ['ok'=>$code>=200&&$code<300, 'code'=>$code,'data'=>$data,'err'=>$err];
  }

  /** Kargo onayı */
  public function confirmShipment(string $orderNumber, array $payload): array {
    // TY kargo onay stub (gerçek endpoint politikaya göre değişebilir)
    $url=$this->base.'/suppliers/'.$this->supplier.'/orders/shipment/confirm';
    [$code,$data,$err]=\App\Utils\Http::json('POST',$url,$this->headers(),['orderNumber'=>$orderNumber]+$payload,60);
    return ['ok'=>$code>=200&&$code<300,'code'=>$code,'data'=>$data,'err'=>$err];
  }
}
