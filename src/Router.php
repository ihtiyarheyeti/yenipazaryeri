<?php
namespace App;

use App\Controllers\TrendyolController;
use App\Controllers\ConnectionsController;

final class Router {
  private array $routes = ['GET'=>[],'POST'=>[],'PUT'=>[],'DELETE'=>[],'OPTIONS'=>[]];

  public function get($p,$h){    $this->routes['GET'][$p]=$h; }
  public function post($p,$h){   $this->routes['POST'][$p]=$h; }
  public function put($p,$h){    $this->routes['PUT'][$p]=$h; }
  public function delete($p,$h){ $this->routes['DELETE'][$p]=$h; }
  public function options($p,$h){$this->routes['OPTIONS'][$p]=$h; }

  public function getRoutes(string $method): array {
    return $this->routes[$method] ?? [];
  }

  public function dispatch(string $method,string $uri): array {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if ($method === 'OPTIONS') {
      http_response_code(204);
      echo json_encode(['ok'=>true,'preflight'=>true]);
      return ['ok'=>true,'preflight'=>true];
    }

    $path = parse_url($uri, PHP_URL_PATH);

    $scriptDir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($scriptDir && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
      $path = substr($path, strlen($scriptDir));
    }

    if (strpos($path, '/index.php') === 0) {
      $path = substr($path, strlen('/index.php'));
    }

    $body = self::readJson();
    $query = $_GET;

    if (isset($this->routes[$method][$path])) {
      return $this->invoke($this->routes[$method][$path], [], $body, $query);
    }

    foreach ($this->routes[$method] as $pattern=>$handler) {
      $regex = '#^'.preg_replace('#\{[a-zA-Z_]+\}#','([a-zA-Z0-9_-]+)',$pattern).'$#';
      if (preg_match($regex,$path,$m)) {
        array_shift($m);
        return $this->invoke($handler, $m, $body, $query);
      }
    }

    http_response_code(404);
    $response = [
      'ok'=>false,
      'error'=>'Not Found',
      'path'=>$path,
      'method'=>$method,
      'available_routes'=>array_keys($this->routes[$method] ?? [])
    ];
    echo json_encode($response, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    return $response;
  }

  private function invoke($handler, array $params, array $body, array $query): array {
    if (is_array($handler)) {
      [$class,$method] = $handler;
      $ctrl = new $class();
      return $ctrl->$method($params, $body, $query);
    } else {
      return call_user_func($handler, $params, $body, $query);
    }
  }

  public static function readJson(): array {
    $raw = file_get_contents('php://input') ?: '';
    $d = json_decode($raw,true);
    return is_array($d) ? $d : [];
  }
}

/**
 * === Route Tanımları ===
 */
$router = new Router();

/* -------------------------------------------------
 * Sağlık kontrolü
 * -------------------------------------------------*/
$router->get('/health', function($params,$body,$query){
  return ['ok'=>true,'time'=>date('c')];
});

/* -------------------------------------------------
 * Trendyol routes
 * -------------------------------------------------*/
$router->get('/api/trendyol/products', [TrendyolController::class, 'listProducts']);
$router->get('/api/trendyol/orders',   [TrendyolController::class, 'listOrders']);
$router->post('/api/trendyol/products',[TrendyolController::class, 'createOrUpdateProducts']);

/* -------------------------------------------------
 * Woo — Teşhis
 * -------------------------------------------------*/
$router->get('/diag/woo/products', function($params,$body,$query){
  try{
    $pdo = \App\Database::pdo();
    $st  = $pdo->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_id=2 ORDER BY id DESC LIMIT 1");
    $st->execute([\App\Context::$tenantId]);
    $conn = $st->fetch();
    if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];

    $woo = new \App\Integrations\WooAdapter($conn);
    $res = $woo->listProducts(1,3,null);
    return ['ok'=>($res['ok']??false),'sample'=>$res['data']??$res];
  }catch(\Throwable $e){
    http_response_code(500);
    return ['ok'=>false,'error'=>'diag_exception','message'=>$e->getMessage()];
  }
});

/* -------------------------------------------------
 * Woo — Import
 * -------------------------------------------------*/
$router->post('/import/woo', function($params,$body,$query){
  try{
    $tenantId = \App\Context::$tenantId ?? 1;
    $pdo      = \App\Database::pdo();

    $st=$pdo->prepare("SELECT * FROM marketplace_connections WHERE tenant_id=? AND marketplace_id=2 ORDER BY id DESC LIMIT 1");
    $st->execute([$tenantId]);
    $conn=$st->fetch();
    if(!$conn) return ['ok'=>false,'error'=>'conn_woo_missing'];

    $page = (int)($query['page'] ?? 1);
    $per  = (int)($query['per']  ?? 50);

    $woo = new \App\Integrations\WooAdapter($conn);
    $res = $woo->listProducts($page,$per,null);
    if(empty($res['ok'])) return ['ok'=>false,'error'=>'woo_http_'.($res['code']??'unknown')];

    $imported=0; $updated=0;
    $messages=[];

    foreach(($res['data']??[]) as $pjson){
      $origin     = 'woo';
      $originId   = (string)($pjson['id'] ?? '');
      if($originId===''){ continue; }

      $name  = $pjson['name'] ?? 'No Name';
      $desc  = $pjson['description'] ?? ($pjson['short_description'] ?? null);

      $price = null;
      if (isset($pjson['price']) && $pjson['price'] !== '') {
        $price = (float)$pjson['price'];
      } elseif (isset($pjson['regular_price']) && $pjson['regular_price'] !== '') {
        $price = (float)$pjson['regular_price'];
      }

      $stock = isset($pjson['stock_quantity']) ? (int)$pjson['stock_quantity'] : 0;

      $stFind=$pdo->prepare("SELECT id FROM products WHERE origin_mp=? AND origin_external_id=? LIMIT 1");
      $stFind->execute([$origin,$originId]);
      $pid=(int)($stFind->fetchColumn()?:0);

      $categoryPath = null;
      $categoryMatch= 'unmapped';

      if(!$pid){
        $ins=$pdo->prepare("INSERT INTO products(tenant_id,name,brand,description,category_path,origin_mp,origin_external_id,category_match,status,created_at,updated_at)
                            VALUES (?,?,?,?,?,?,?, ?, 'active', NOW(),NOW())");
        $ins->execute([$tenantId,$name,null,$desc,$categoryPath,$origin,$originId,$categoryMatch]);
        $pid=(int)$pdo->lastInsertId();
        $imported++;
        $messages[]="Ürün import edildi: ".$name;
      }else{
        $upd=$pdo->prepare("UPDATE products SET name=?, description=?, category_path=?, category_match=?, updated_at=NOW() WHERE id=?");
        $upd->execute([$name,$desc,$categoryPath,$categoryMatch,$pid]);
        $updated++;
        $messages[]="Ürün güncellendi: ".$name;
      }

      $sku = $pjson['sku'] ?? ('AUTO-'.substr(md5(($name?:'P').'-'.$originId),0,10));
      $stVar=$pdo->prepare("SELECT id FROM variants WHERE tenant_id=? AND sku=? LIMIT 1");
      $stVar->execute([$tenantId,$sku]);
      $vid=(int)($stVar->fetchColumn()?:0);

      $attrs = [];
      if(!$vid){
        $insV=$pdo->prepare("INSERT INTO variants(tenant_id,product_id,sku,price,stock,attrs_json,origin_mp,created_at,updated_at)
                             VALUES (?,?,?,?,?,?,?,NOW(),NOW())");
        $insV->execute([$tenantId,$pid,$sku,$price,$stock,json_encode($attrs,JSON_UNESCAPED_UNICODE),$origin]);
      }else{
        $updV=$pdo->prepare("UPDATE variants SET product_id=?, price=?, stock=?, attrs_json=?, origin_mp=?, updated_at=NOW() WHERE id=?");
        $updV->execute([$pid,$price,$stock,json_encode($attrs,JSON_UNESCAPED_UNICODE),$origin,$vid]);
      }
    }

    return [
      'ok'=>true,
      'imported_count'=>$imported,
      'updated_count'=>$updated,
      'page'=>$page,
      'messages'=>$messages
    ];
  }catch(\Throwable $e){
    http_response_code(500);
    return ['ok'=>false,'error'=>'import_exception','message'=>$e->getMessage()];
  }
});

/* -------------------------------------------------
 * Connections routes
 * -------------------------------------------------*/
$router->get('/connections', [ConnectionsController::class, 'index']);
$router->post('/connections', [ConnectionsController::class, 'store']);
$router->get('/connections/{id}/test', [ConnectionsController::class, 'test']);
